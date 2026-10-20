<?php

namespace App\Services;

use App\Enums\StatusInsiden;
use App\Enums\StatusPersetujuanPengeluaran;
use App\Enums\StatusVerifikasi;
use App\Models\Insiden;
use App\Models\Item;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Rekonsiliasi;
use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private readonly PengaturanService $pengaturan) {}

    /**
     * @return array<string, mixed>
     */
    public function ringkasan(Carbon $mulai, Carbon $selesai, ?int $unitId): array
    {
        return $this->ingat('ringkasan', $mulai, $selesai, $unitId, function () use ($mulai, $selesai, $unitId) {
            $pemasukan = $this->pemasukanQuery($mulai, $selesai, $unitId);
            $pengeluaran = $this->pengeluaranQuery($mulai, $selesai, $unitId);

            $totalPemasukan = (int) (clone $pemasukan)->sum('nominal');
            $totalPengeluaran = (int) (clone $pengeluaran)->sum('total_nominal');

            $terverifikasi = (int) (clone $pemasukan)
                ->whereIn('status_verifikasi', [
                    StatusVerifikasi::SESUAI->value,
                    StatusVerifikasi::SELISIH->value,
                    StatusVerifikasi::SELESAI->value,
                ])
                ->sum('nominal');

            $panjang = $mulai->diffInDays($selesai) + 1;
            $prevMulai = $mulai->copy()->subDays($panjang);
            $prevSelesai = $selesai->copy()->subDays($panjang);

            $prevPemasukan = (int) $this->pemasukanQuery($prevMulai, $prevSelesai, $unitId)->sum('nominal');
            $prevPengeluaran = (int) $this->pengeluaranQuery($prevMulai, $prevSelesai, $unitId)->sum('total_nominal');

            $laba = $totalPemasukan - $totalPengeluaran;
            $selisihTerbuka = $this->insidenTerbukaQuery($unitId);

            return [
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'laba' => $laba,
                'margin' => $totalPemasukan > 0 ? round($laba / $totalPemasukan * 100, 2) : 0.0,
                'terverifikasi_nominal' => $terverifikasi,
                'terverifikasi_persen' => $totalPemasukan > 0 ? round($terverifikasi / $totalPemasukan * 100, 2) : 0.0,
                'perubahan_pemasukan' => $this->perubahan($totalPemasukan, $prevPemasukan),
                'perubahan_pengeluaran' => $this->perubahan($totalPengeluaran, $prevPengeluaran),
                'selisih_terbuka_jumlah' => (int) (clone $selisihTerbuka)->count(),
                'selisih_terbuka_nominal' => (int) (clone $selisihTerbuka)->sum('nominal_selisih'),
                'selisih_dihapuskan' => (int) Insiden::query()
                    ->where('status', StatusInsiden::DIHAPUSKAN->value)
                    ->whereBetween('ditutup_at', [$mulai->copy()->startOfDay(), $selesai->copy()->endOfDay()])
                    ->when($unitId, fn ($q) => $q->whereHas('rekonsiliasi', fn ($r) => $r->where('unit_usaha_id', $unitId)))
                    ->sum('nominal_selisih'),
            ];
        });
    }

    /**
     * @return array{labels: array<int, string>, pemasukan: array<int, int>, pengeluaran: array<int, int>}
     */
    public function trenHarian(Carbon $mulai, Carbon $selesai, ?int $unitId): array
    {
        return $this->ingat('tren', $mulai, $selesai, $unitId, function () use ($mulai, $selesai, $unitId) {
            $pemasukan = $this->pemasukanQuery($mulai, $selesai, $unitId)
                ->selectRaw('DATE(tanggal) as hari, SUM(nominal) as total')
                ->groupBy('hari')
                ->pluck('total', 'hari');

            $pengeluaran = $this->pengeluaranQuery($mulai, $selesai, $unitId)
                ->selectRaw('DATE(tanggal) as hari, SUM(total_nominal) as total')
                ->groupBy('hari')
                ->pluck('total', 'hari');

            $labels = [];
            $dataPemasukan = [];
            $dataPengeluaran = [];

            $bulanan = $mulai->diffInDays($selesai) > 92;

            if ($bulanan) {
                $cursor = $mulai->copy()->startOfMonth();
                while ($cursor->lte($selesai)) {
                    $kunci = $cursor->format('Y-m');
                    $labels[] = $cursor->translatedFormat('M Y');
                    $dataPemasukan[] = (int) $pemasukan
                        ->filter(fn ($total, $hari) => str_starts_with((string) $hari, $kunci))
                        ->sum();
                    $dataPengeluaran[] = (int) $pengeluaran
                        ->filter(fn ($total, $hari) => str_starts_with((string) $hari, $kunci))
                        ->sum();
                    $cursor->addMonth();
                }
            } else {
                $cursor = $mulai->copy()->startOfDay();
                while ($cursor->lte($selesai)) {
                    $kunci = $cursor->toDateString();
                    $labels[] = $cursor->day.'/'.$cursor->month;
                    $dataPemasukan[] = (int) ($pemasukan[$kunci] ?? 0);
                    $dataPengeluaran[] = (int) ($pengeluaran[$kunci] ?? 0);
                    $cursor->addDay();
                }
            }

            return [
                'labels' => $labels,
                'pemasukan' => $dataPemasukan,
                'pengeluaran' => $dataPengeluaran,
            ];
        });
    }

    /**
     * @return array{labels: array<int, string>, datasets: array<int, array<string, mixed>>}
     */
    public function pemasukanPerUnit(Carbon $mulai, Carbon $selesai, ?int $unitId): array
    {
        return $this->ingat('per-unit-chart', $mulai, $selesai, $unitId, function () use ($mulai, $selesai, $unitId) {
            $units = UnitUsaha::query()
                ->when($unitId, fn ($q) => $q->whereKey($unitId))
                ->orderBy('nama')
                ->get();

            $rows = $this->pemasukanQuery($mulai, $selesai, $unitId)
                ->join('unit_usaha', 'unit_usaha.id', '=', 'pemasukan.unit_usaha_id')
                ->selectRaw('DATE(pemasukan.tanggal) as hari, unit_usaha.nama as unit, SUM(pemasukan.nominal) as total')
                ->groupBy('hari', 'unit')
                ->get();

            $tanggal = collect();
            $cursor = $mulai->copy();
            while ($cursor->lte($selesai)) {
                $tanggal->push($cursor->toDateString());
                $cursor->addDay();
            }

            $warna = ['#f97316', '#0ea5e9', '#22c55e', '#a855f7', '#ef4444'];

            $datasets = $units->values()->map(function (UnitUsaha $unit, int $index) use ($rows, $tanggal, $warna) {
                $perHari = $rows->where('unit', $unit->nama)->pluck('total', 'hari');

                return [
                    'label' => $unit->nama,
                    'data' => $tanggal->map(fn ($t) => (int) ($perHari[$t] ?? 0))->all(),
                    'backgroundColor' => $warna[$index % count($warna)],
                ];
            })->all();

            return [
                'labels' => $tanggal->map(fn ($t) => Carbon::parse($t)->format('d/m'))->all(),
                'datasets' => $datasets,
            ];
        });
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function komposisiMetode(Carbon $mulai, Carbon $selesai, ?int $unitId): array
    {
        return $this->ingat('metode', $mulai, $selesai, $unitId, function () use ($mulai, $selesai, $unitId) {
            $rows = $this->pemasukanQuery($mulai, $selesai, $unitId)
                ->join('metode_pembayaran', 'metode_pembayaran.id', '=', 'pemasukan.metode_pembayaran_id')
                ->selectRaw('metode_pembayaran.nama as metode, SUM(pemasukan.nominal) as total')
                ->groupBy('metode')
                ->orderByDesc('total')
                ->get();

            return [
                'labels' => $rows->pluck('metode')->all(),
                'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ];
        });
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function topJenisPengeluaran(Carbon $mulai, Carbon $selesai, ?int $unitId): array
    {
        return $this->ingat('top-jenis', $mulai, $selesai, $unitId, function () use ($mulai, $selesai, $unitId) {
            $rows = $this->pengeluaranQuery($mulai, $selesai, $unitId)
                ->join('jenis_pengeluaran', 'jenis_pengeluaran.id', '=', 'pengeluaran.jenis_pengeluaran_id')
                ->selectRaw('jenis_pengeluaran.nama as jenis, SUM(pengeluaran.total_nominal) as total')
                ->groupBy('jenis')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            return [
                'labels' => $rows->pluck('jenis')->all(),
                'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function ringkasanPerUnit(Carbon $mulai, Carbon $selesai, ?int $unitId): array
    {
        return $this->ingat('tabel-unit', $mulai, $selesai, $unitId, function () use ($mulai, $selesai, $unitId) {
            $units = UnitUsaha::query()
                ->when($unitId, fn ($q) => $q->whereKey($unitId))
                ->orderBy('nama')
                ->get();

            $pemasukan = $this->pemasukanQuery($mulai, $selesai, $unitId)
                ->selectRaw('unit_usaha_id, SUM(nominal) as total, SUM(CASE WHEN status_verifikasi IN (?, ?, ?) THEN nominal ELSE 0 END) as terverifikasi', [
                    StatusVerifikasi::SESUAI->value,
                    StatusVerifikasi::SELISIH->value,
                    StatusVerifikasi::SELESAI->value,
                ])
                ->groupBy('unit_usaha_id')
                ->get()
                ->keyBy('unit_usaha_id');

            $pengeluaran = $this->pengeluaranQuery($mulai, $selesai, $unitId)
                ->selectRaw('unit_usaha_id, SUM(total_nominal) as total')
                ->groupBy('unit_usaha_id')
                ->get()
                ->keyBy('unit_usaha_id');

            return $units->map(function (UnitUsaha $unit) use ($pemasukan, $pengeluaran) {
                $masuk = (int) ($pemasukan[$unit->id]->total ?? 0);
                $keluar = (int) ($pengeluaran[$unit->id]->total ?? 0);
                $terverifikasi = (int) ($pemasukan[$unit->id]->terverifikasi ?? 0);
                $laba = $masuk - $keluar;

                return [
                    'unit' => $unit,
                    'pemasukan' => $masuk,
                    'pengeluaran' => $keluar,
                    'laba' => $laba,
                    'margin' => $masuk > 0 ? round($laba / $masuk * 100, 2) : 0.0,
                    'terverifikasi_persen' => $masuk > 0 ? round($terverifikasi / $masuk * 100, 2) : 0.0,
                ];
            })->all();
        });
    }

    /**
     * Panel Perlu Perhatian.
     *
     * @return array<string, mixed>
     */
    public function perluPerhatian(User $user, ?int $unitId = null): array
    {
        $unitId = $user->isStaf() ? null : $unitId;
        $batasVerifikasi = $this->pengaturan->getInt('batas_hari_verifikasi', 2);
        $batasSettlement = $this->pengaturan->getInt('batas_hari_settlement', 3);
        $batasLampiran = $this->pengaturan->getInt('batas_lampiran_pengeluaran', 50000);

        $kunci = 'perhatian.'.$user->id.'.'.($unitId ?? 'all');
        $data = Cache::remember($kunci, 60, function () use ($user, $unitId, $batasVerifikasi, $batasSettlement, $batasLampiran) {
            $unitFilter = function ($query) use ($unitId) {
                if ($unitId !== null) {
                    $query->where('unit_usaha_id', $unitId);
                }
            };

            $belumDicek = Pemasukan::query()
                ->belumDicek()
                ->whereDate('tanggal', '<=', today()->subDays($batasVerifikasi))
                ->when($user->isStaf(), fn ($q) => $q->whereIn('unit_usaha_id', $user->unitUsaha()->select('unit_usaha.id')))
                ->when($unitId, $unitFilter)
                ->with(['unitUsaha', 'metode'])
                ->orderBy('tanggal')
                ->get()
                ->groupBy(fn (Pemasukan $p) => $p->unit_usaha_id.'-'.$p->metode_pembayaran_id)
                ->map(function (Collection $grup) {
                    $pertama = $grup->first();

                    return [
                        'unit_id' => $pertama->unit_usaha_id,
                        'metode_id' => $pertama->metode_pembayaran_id,
                        'unit' => $pertama->unitUsaha->nama,
                        'metode' => $pertama->metode->nama,
                        'jumlah' => $grup->count(),
                        'total' => (int) $grup->sum('nominal'),
                        'tertua' => $grup->min('tanggal'),
                    ];
                })->values()->all();

            $insiden = Insiden::query()
                ->terbuka()
                ->when($unitId, fn ($q) => $q->whereHas('rekonsiliasi', fn ($r) => $r->where('unit_usaha_id', $unitId)))
                ->with('rekonsiliasi.unitUsaha')
                ->orderBy('created_at')
                ->limit(10)
                ->get();

            $settlement = Pemasukan::query()
                ->where('status_verifikasi', StatusVerifikasi::MENUNGGU_SETTLEMENT->value)
                ->whereDate('tanggal', '<=', today()->subDays($batasSettlement))
                ->when($user->isStaf(), fn ($q) => $q->whereIn('unit_usaha_id', $user->unitUsaha()->select('unit_usaha.id')))
                ->when($unitId, $unitFilter)
                ->with(['unitUsaha', 'metode'])
                ->get()
                ->groupBy(fn (Pemasukan $p) => $p->unit_usaha_id.'-'.$p->metode_pembayaran_id)
                ->map(function (Collection $grup) {
                    $pertama = $grup->first();

                    return [
                        'unit' => $pertama->unitUsaha->nama,
                        'metode' => $pertama->metode->nama,
                        'jumlah' => $grup->count(),
                        'total' => (int) $grup->sum('nominal'),
                        'tertua' => $grup->min('tanggal'),
                    ];
                })->values()->all();

            $tanpaLampiran = Pengeluaran::query()
                ->dihitung()
                ->where('total_nominal', '>=', $batasLampiran)
                ->where(fn ($q) => $q->whereNull('lampiran')->orWhere('lampiran', '[]')->orWhere('lampiran', json_encode([])))
                ->when($user->isStaf(), fn ($q) => $q->whereIn('unit_usaha_id', $user->unitUsaha()->select('unit_usaha.id')))
                ->when($unitId, $unitFilter)
                ->with(['unitUsaha', 'jenis'])
                ->latest('tanggal')
                ->limit(10)
                ->get();

            $menungguPersetujuan = Pengeluaran::query()
                ->where('status_persetujuan', StatusPersetujuanPengeluaran::MENUNGGU->value)
                ->when($user->isStaf(), fn ($q) => $q->whereIn('unit_usaha_id', $user->unitUsaha()->select('unit_usaha.id')))
                ->when($unitId, $unitFilter)
                ->with(['unitUsaha', 'dibuatOleh'])
                ->latest('tanggal')
                ->limit(10)
                ->get();

            return [
                'belum_dicek' => $belumDicek,
                'insiden' => $insiden,
                'settlement' => $settlement,
                'tanpa_lampiran' => $tanpaLampiran,
                'menunggu_persetujuan' => $menungguPersetujuan,
                'lonjakan_harga' => $this->lonjakanHarga(),
            ];
        });

        $data['selisih_dihapuskan'] = Insiden::query()
            ->where('status', StatusInsiden::DIHAPUSKAN->value)
            ->when($unitId, fn ($q) => $q->whereHas('rekonsiliasi', fn ($r) => $r->where('unit_usaha_id', $unitId)))
            ->whereBetween('ditutup_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('nominal_selisih');

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lonjakanHarga(): array
    {
        $batas = $this->pengaturan->getInt('lonjakan_harga_persen', 20);

        $details = DB::table('detail_pengeluaran')
            ->join('pengeluaran', 'pengeluaran.id', '=', 'detail_pengeluaran.pengeluaran_id')
            ->join('item', 'item.id', '=', 'detail_pengeluaran.item_id')
            ->leftJoin('satuan', 'satuan.id', '=', 'detail_pengeluaran.satuan_id')
            ->whereNull('pengeluaran.deleted_at')
            ->where('pengeluaran.status_persetujuan', StatusPersetujuanPengeluaran::DISETUJUI->value)
            ->whereDate('pengeluaran.tanggal', '>=', now()->subDays(90))
            ->orderByDesc('detail_pengeluaran.id')
            ->select([
                'detail_pengeluaran.item_id',
                'item.nama as item',
                'detail_pengeluaran.harga_satuan',
                'satuan.singkatan as satuan',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY detail_pengeluaran.item_id ORDER BY detail_pengeluaran.id DESC) as urutan'),
            ])
            ->get();

        return $details->groupBy('item_id')
            ->map(function (Collection $grup) use ($batas) {
                $terbaru = $grup->first();
                $sebelumnya = $grup->skip(1)->take(5);

                if ($sebelumnya->isEmpty()) {
                    return null;
                }

                $rata = (float) $sebelumnya->avg('harga_satuan');

                if ($rata <= 0 || $terbaru->harga_satuan <= $rata * (1 + $batas / 100)) {
                    return null;
                }

                return [
                    'item' => $terbaru->item,
                    'satuan' => $terbaru->satuan,
                    'harga' => (int) $terbaru->harga_satuan,
                    'rata_rata' => (int) round($rata),
                    'persen' => round((($terbaru->harga_satuan - $rata) / $rata) * 100, 1),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Ringkasan khusus staf: input hari ini miliknya di unit yang ditugaskan.
     *
     * @return array<string, mixed>
     */
    public function ringkasanStaf(User $user, ?int $unitId = null): array
    {
        $unitIds = $unitId !== null && $user->bolehAksesUnit($unitId)
            ? [$unitId]
            : $user->unitUsaha()->pluck('unit_usaha.id')->all();

        $query = Pemasukan::query()
            ->where('dibuat_oleh', $user->id)
            ->whereDate('tanggal', today())
            ->whereIn('unit_usaha_id', $unitIds);

        $perStatus = (clone $query)
            ->selectRaw('status_verifikasi, COUNT(*) as jumlah, SUM(nominal) as total')
            ->groupBy('status_verifikasi')
            ->get();

        $pengeluaran = Pengeluaran::query()
            ->where('dibuat_oleh', $user->id)
            ->whereDate('tanggal', today())
            ->whereIn('unit_usaha_id', $unitIds);

        return [
            'jumlah_pemasukan' => (int) (clone $query)->count(),
            'total_pemasukan' => (int) (clone $query)->sum('nominal'),
            'per_status' => $perStatus,
            'total_pengeluaran' => (int) (clone $pengeluaran)->sum('total_nominal'),
            'jumlah_pengeluaran' => (int) (clone $pengeluaran)->count(),
        ];
    }

    public function flush(): void
    {
        if (Cache::has('dashboard.versi')) {
            Cache::increment('dashboard.versi');
        } else {
            Cache::forever('dashboard.versi', 1);
        }
    }

    private function insidenTerbukaQuery(?int $unitId)
    {
        return Insiden::query()
            ->terbuka()
            ->when($unitId, fn ($q) => $q->whereHas('rekonsiliasi', fn ($r) => $r->where('unit_usaha_id', $unitId)));
    }

    private function pemasukanQuery(Carbon $mulai, Carbon $selesai, ?int $unitId)
    {
        return Pemasukan::query()
            ->whereBetween('tanggal', [$mulai->copy()->toDateString(), $selesai->copy()->toDateString()])
            ->when($unitId, fn ($q) => $q->where('unit_usaha_id', $unitId));
    }

    private function pengeluaranQuery(Carbon $mulai, Carbon $selesai, ?int $unitId)
    {
        return Pengeluaran::query()
            ->dihitung()
            ->whereBetween('tanggal', [$mulai->copy()->toDateString(), $selesai->copy()->toDateString()])
            ->when($unitId, fn ($q) => $q->where('unit_usaha_id', $unitId));
    }

    private function perubahan(int $sekarang, int $sebelumnya): ?float
    {
        if ($sebelumnya === 0) {
            return $sekarang === 0 ? 0.0 : null;
        }

        return round((($sekarang - $sebelumnya) / $sebelumnya) * 100, 2);
    }

    private function ingat(string $jenis, Carbon $mulai, Carbon $selesai, ?int $unitId, callable $callback): mixed
    {
        $versi = Cache::get('dashboard.versi', 0);
        $kunci = 'dashboard.'.$jenis.'.'.$versi.'.'.$mulai->toDateString().'.'.$selesai->toDateString().'.'.($unitId ?? 'all');

        return Cache::remember($kunci, 60, $callback);
    }
}
