<?php

namespace App\Services;

use App\Enums\ArahSelisih;
use App\Enums\StatusRekonsiliasi;
use App\Enums\StatusVerifikasi;
use App\Enums\TipeMetodePembayaran;
use App\Models\MetodePembayaran;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Rekonsiliasi;
use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RekonsiliasiService
{
    public function __construct(
        private readonly PengaturanService $pengaturan,
        private readonly PeriodeService $periode,
        private readonly LampiranService $lampiran,
        private readonly InsidenService $insiden,
        private readonly NotifikasiService $notifikasi,
    ) {}

    /**
     * @return Collection<int, Pemasukan>
     */
    public function kandidat(int $unitId, int $metodeId, string $mulai, string $selesai): Collection
    {
        return Pemasukan::query()
            ->with(['kategori', 'dibuatOleh'])
            ->where('unit_usaha_id', $unitId)
            ->where('metode_pembayaran_id', $metodeId)
            ->whereBetween('tanggal', [
                Carbon::parse($mulai)->toDateString(),
                Carbon::parse($selesai)->toDateString(),
            ])
            ->dapatDirekonsiliasi()
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();
    }

    public function potonganKas(int $unitId, int $metodeId, string $mulai, string $selesai, ?int $rekonsiliasiId = null): int
    {
        $metode = MetodePembayaran::query()->find($metodeId);

        if ($metode?->tipe !== TipeMetodePembayaran::TUNAI) {
            return 0;
        }

        return (int) Pengeluaran::query()
            ->dihitung()
            ->kasHarian()
            ->where('unit_usaha_id', $unitId)
            ->whereBetween('tanggal', [
                Carbon::parse($mulai)->toDateString(),
                Carbon::parse($selesai)->toDateString(),
            ])
            ->where(function ($q) use ($rekonsiliasiId) {
                $q->whereNull('rekonsiliasi_id');

                if ($rekonsiliasiId !== null) {
                    $q->orWhere('rekonsiliasi_id', $rekonsiliasiId);
                }
            })
            ->sum('total_nominal');
    }

    public function hitungSelisih(int $totalDicatat, int $totalDiterima, int $biayaAdmin, int $potonganKas): int
    {
        return $totalDiterima - ($totalDicatat - $biayaAdmin - $potonganKas);
    }

    public function saranBiayaAdmin(int $totalDicatat, int $metodeId): int
    {
        $metode = MetodePembayaran::query()->find($metodeId);

        if ($metode?->tipe !== TipeMetodePembayaran::QRIS) {
            return 0;
        }

        return (int) round($totalDicatat * $this->pengaturan->getDecimal('mdr_qris_persen', 0.7) / 100);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function pratinjau(array $data): array
    {
        $mulai = Carbon::parse($data['periode_mulai'])->toDateString();
        $selesai = Carbon::parse($data['periode_selesai'])->toDateString();
        $unitId = (int) ($data['unit_usaha_id'] ?? 0);
        $metodeId = (int) ($data['metode_pembayaran_id'] ?? 0);

        $pemasukan = Pemasukan::query()
            ->whereIn('id', $data['pemasukan_ids'] ?? [])
            ->where('unit_usaha_id', $unitId)
            ->where('metode_pembayaran_id', $metodeId)
            ->whereBetween('tanggal', [$mulai, $selesai])
            ->dapatDirekonsiliasi()
            ->get();

        $totalDicatat = (int) $pemasukan->sum('nominal');
        $potongan = $this->potonganKas($unitId, $metodeId, $mulai, $selesai);
        $biaya = (int) parse_rupiah($data['biaya_admin'] ?? 0);
        $diterima = (int) parse_rupiah($data['total_diterima'] ?? 0);
        $selisih = $this->hitungSelisih($totalDicatat, $diterima, $biaya, $potongan);
        $toleransi = $this->pengaturan->getInt('toleransi_selisih', 1000);

        return [
            'total_dicatat' => $totalDicatat,
            'potongan_kas' => $potongan,
            'biaya_admin' => $biaya,
            'total_diterima' => $diterima,
            'selisih' => $selisih,
            'arah' => $selisih === 0 ? null : ArahSelisih::dari($selisih),
            'dalam_toleransi' => abs($selisih) <= $toleransi,
            'jumlah_pemasukan' => $pemasukan->count(),
            'saran_biaya_admin' => $this->saranBiayaAdmin($totalDicatat, $metodeId),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{rekonsiliasi: Rekonsiliasi|null, insiden: \App\Models\Insiden|null, menunggu: bool}
     */
    public function simpan(array $data, User $user): array
    {
        $mulai = Carbon::parse($data['periode_mulai'])->toDateString();
        $selesai = Carbon::parse($data['periode_selesai'])->toDateString();
        $unitId = (int) ($data['unit_usaha_id'] ?? 0);
        $metodeId = (int) ($data['metode_pembayaran_id'] ?? 0);
        $menunggu = (bool) ($data['menunggu_settlement'] ?? false);

        $this->validasi($data, $user);
        $this->pastikanRentangTerbuka($mulai, $selesai, $unitId);

        return DB::transaction(function () use ($data, $user, $mulai, $selesai, $unitId, $metodeId, $menunggu) {
            $ids = array_map('intval', $data['pemasukan_ids'] ?? []);

            $pemasukan = Pemasukan::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            $this->pastikanKandidatValid($pemasukan, $unitId, $metodeId, $mulai, $selesai, $ids);

            if ($user->tundukMakerChecker() && $pemasukan->contains(fn (Pemasukan $p) => $p->dibuat_oleh === $user->id)) {
                throw ValidationException::withMessages([
                    'pemasukan_ids' => 'Maker-checker: Anda tidak dapat memverifikasi pemasukan yang Anda buat sendiri.',
                ]);
            }

            if ($menunggu) {
                $metode = MetodePembayaran::query()->findOrFail($metodeId);

                if ($metode->tipe === TipeMetodePembayaran::TUNAI) {
                    throw ValidationException::withMessages([
                        'menunggu_settlement' => 'Tandai menunggu settlement hanya untuk QRIS atau transfer.',
                    ]);
                }

                foreach ($pemasukan as $item) {
                    $item->update([
                        'status_verifikasi' => StatusVerifikasi::MENUNGGU_SETTLEMENT,
                        'diubah_oleh' => $user->id,
                    ]);
                }

                activity('rekonsiliasi')
                    ->causedBy($user)
                    ->withProperties([
                        'pemasukan_ids' => $ids,
                        'unit_usaha_id' => $unitId,
                        'metode_pembayaran_id' => $metodeId,
                        'periode_mulai' => $mulai,
                        'periode_selesai' => $selesai,
                    ])
                    ->log('Menandai pemasukan menunggu settlement');

                return ['rekonsiliasi' => null, 'insiden' => null, 'menunggu' => true];
            }

            $totalDicatat = (int) $pemasukan->sum('nominal');
            $potonganOtomatis = $this->potonganKas($unitId, $metodeId, $mulai, $selesai);
            $potongan = $potonganOtomatis;

            if (array_key_exists('potongan_kas_manual', $data) && $data['potongan_kas_manual'] !== null) {
                $manual = (int) parse_rupiah($data['potongan_kas_manual']);

                if ($manual !== $potonganOtomatis) {
                    if (! $user->isOwner()) {
                        throw ValidationException::withMessages(['potongan_kas_manual' => 'Hanya Owner yang dapat mengoreksi potongan kas.']);
                    }

                    if (trim((string) ($data['catatan'] ?? '')) === '') {
                        throw ValidationException::withMessages(['catatan' => 'Catatan wajib diisi bila mengoreksi potongan kas.']);
                    }

                    $potongan = $manual;
                }
            }

            $biaya = (int) parse_rupiah($data['biaya_admin'] ?? 0);
            $diterima = (int) parse_rupiah($data['total_diterima'] ?? 0);
            $selisih = $this->hitungSelisih($totalDicatat, $diterima, $biaya, $potongan);
            $toleransi = $this->pengaturan->getInt('toleransi_selisih', 1000);
            $status = abs($selisih) <= $toleransi
                ? StatusRekonsiliasi::SESUAI
                : StatusRekonsiliasi::SELISIH;

            $bukti = [];

            if (isset($data['bukti']) && is_array($data['bukti'])) {
                $bukti = array_values(array_filter(
                    $data['bukti'],
                    fn ($item) => is_array($item) && isset($item['path']),
                ));
            } elseif (! empty($data['bukti_files'])) {
                $bukti = $this->lampiran->dariPaths(
                    (array) $data['bukti_files'],
                    (array) ($data['bukti_nama_asli'] ?? []),
                    $user,
                );
            }

            $rekonsiliasi = Rekonsiliasi::query()->create([
                'kode' => $this->kodeBaru(),
                'unit_usaha_id' => $unitId,
                'metode_pembayaran_id' => $metodeId,
                'periode_mulai' => $mulai,
                'periode_selesai' => $selesai,
                'tanggal_diterima' => Carbon::parse($data['tanggal_diterima'])->toDateString(),
                'total_dicatat' => $totalDicatat,
                'potongan_kas' => $potongan,
                'biaya_admin' => $biaya,
                'total_diterima' => $diterima,
                'selisih' => $selisih,
                'status' => $status,
                'catatan' => $data['catatan'] ?? null,
                'bukti' => $bukti,
                'diverifikasi_oleh' => $user->id,
                'diverifikasi_at' => now(),
            ]);

            $statusPemasukan = $status === StatusRekonsiliasi::SESUAI
                ? StatusVerifikasi::SESUAI
                : StatusVerifikasi::SELISIH;

            foreach ($pemasukan as $item) {
                $item->update([
                    'status_verifikasi' => $statusPemasukan,
                    'rekonsiliasi_id' => $rekonsiliasi->id,
                    'diubah_oleh' => $user->id,
                ]);
            }

            if ($potongan > 0) {
                Pengeluaran::query()
                    ->dihitung()
                    ->kasHarian()
                    ->where('unit_usaha_id', $unitId)
                    ->whereBetween('tanggal', [$mulai, $selesai])
                    ->whereNull('rekonsiliasi_id')
                    ->update(['rekonsiliasi_id' => $rekonsiliasi->id]);
            }

            $insiden = null;

            if ($status === StatusRekonsiliasi::SELISIH) {
                $insiden = $this->insiden->buatOtomatis($rekonsiliasi->refresh(), $user);
            }

            $this->notifikasi->rekonsiliasiBaru($rekonsiliasi, $user);
            $this->flushCache();

            return ['rekonsiliasi' => $rekonsiliasi->refresh(), 'insiden' => $insiden, 'menunggu' => false];
        });
    }

    public function batal(Rekonsiliasi $rekonsiliasi, string $alasan, User $user): Rekonsiliasi
    {
        if (! $user->isOwner()) {
            throw ValidationException::withMessages(['alasan' => 'Hanya Owner yang dapat membatalkan rekonsiliasi.']);
        }

        if (trim($alasan) === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan pembatalan wajib diisi.']);
        }

        if ($rekonsiliasi->status === StatusRekonsiliasi::DIBATALKAN) {
            throw ValidationException::withMessages(['alasan' => 'Rekonsiliasi ini sudah dibatalkan.']);
        }

        $this->pastikanRentangTerbuka(
            $rekonsiliasi->periode_mulai->toDateString(),
            $rekonsiliasi->periode_selesai->toDateString(),
            $rekonsiliasi->unit_usaha_id,
        );

        return DB::transaction(function () use ($rekonsiliasi, $alasan, $user) {
            $rekonsiliasi->loadMissing(['pemasukan', 'insiden', 'potonganPengeluaran']);

            foreach ($rekonsiliasi->pemasukan as $pemasukan) {
                $pemasukan->update([
                    'status_verifikasi' => StatusVerifikasi::BELUM_DICEK,
                    'rekonsiliasi_id' => null,
                    'diubah_oleh' => $user->id,
                ]);
            }

            Pengeluaran::query()->where('rekonsiliasi_id', $rekonsiliasi->id)->update(['rekonsiliasi_id' => null]);

            foreach ($rekonsiliasi->insiden as $insiden) {
                $this->insiden->batalkanKarenaRekonsiliasi($insiden, $user);
            }

            $rekonsiliasi->update([
                'status' => StatusRekonsiliasi::DIBATALKAN,
                'dibatalkan_oleh' => $user->id,
                'dibatalkan_at' => now(),
                'alasan_pembatalan' => $alasan,
            ]);

            activity('rekonsiliasi')
                ->performedOn($rekonsiliasi)
                ->causedBy($user)
                ->withProperties(['alasan' => $alasan])
                ->log('Membatalkan rekonsiliasi');

            $this->flushCache();

            return $rekonsiliasi->refresh();
        });
    }

    /**
     * Hitung ulang rekonsiliasi setelah koreksi data (aturan 20).
     */
    public function hitungUlang(Rekonsiliasi $rekonsiliasi, string $alasan, User $user): Rekonsiliasi
    {
        if ($rekonsiliasi->status === StatusRekonsiliasi::DIBATALKAN) {
            return $rekonsiliasi;
        }

        return DB::transaction(function () use ($rekonsiliasi, $alasan, $user) {
            $rekonsiliasi->refresh()->load('pemasukan');

            $totalDicatat = (int) $rekonsiliasi->pemasukan->sum('nominal');
            $potongan = (int) Pengeluaran::query()
                ->dihitung()
                ->where('rekonsiliasi_id', $rekonsiliasi->id)
                ->sum('total_nominal');

            $selisih = $this->hitungSelisih($totalDicatat, $rekonsiliasi->total_diterima, $rekonsiliasi->biaya_admin, $potongan);
            $toleransi = $this->pengaturan->getInt('toleransi_selisih', 1000);
            $dalamToleransi = abs($selisih) <= $toleransi;

            $statusPemasukan = $dalamToleransi ? StatusVerifikasi::SESUAI : StatusVerifikasi::SELISIH;

            foreach ($rekonsiliasi->pemasukan as $pemasukan) {
                if ($pemasukan->status_verifikasi !== StatusVerifikasi::SELESAI) {
                    $pemasukan->update(['status_verifikasi' => $statusPemasukan]);
                }
            }

            $rekonsiliasi->update([
                'total_dicatat' => $totalDicatat,
                'potongan_kas' => $potongan,
                'selisih' => $selisih,
                'status' => $dalamToleransi ? StatusRekonsiliasi::SESUAI : StatusRekonsiliasi::SELISIH,
            ]);

            if ($dalamToleransi) {
                foreach ($rekonsiliasi->insiden()->terbuka()->get() as $insiden) {
                    $this->insiden->selesaikanKarenaKoreksi($insiden, $user, $alasan);
                }

                $this->insiden->tutupRekonsiliasiBilaSelesai($rekonsiliasi->refresh());
            } else {
                $insiden = $rekonsiliasi->insiden()->whereIn('status', ['OPEN', 'INVESTIGASI', 'SELESAI'])->latest('id')->first();

                if ($insiden !== null) {
                    $insiden->update([
                        'nominal_selisih' => abs($selisih),
                        'arah' => ArahSelisih::dari($selisih),
                        'status' => \App\Enums\StatusInsiden::OPEN,
                        'ditutup_oleh' => null,
                        'ditutup_at' => null,
                    ]);

                    $this->insiden->catat($insiden, $user, 'Rekonsiliasi dihitung ulang', $alasan);
                } else {
                    $this->insiden->buatOtomatis($rekonsiliasi->refresh(), $user);
                }
            }

            activity('rekonsiliasi')
                ->performedOn($rekonsiliasi)
                ->causedBy($user)
                ->withProperties(['alasan' => $alasan, 'selisih' => $selisih])
                ->log('Menghitung ulang rekonsiliasi');

            $this->flushCache();

            return $rekonsiliasi->refresh();
        });
    }

    public function kodeBaru(): string
    {
        $prefix = 'REK-'.now()->format('Ym').'-';
        $terakhir = Rekonsiliasi::query()
            ->where('kode', 'like', $prefix.'%')
            ->orderByDesc('kode')
            ->value('kode');

        $nomor = $terakhir === null ? 1 : ((int) substr($terakhir, -4)) + 1;

        return $prefix.str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    public function flushCache(): void
    {
        app(DashboardService::class)->flush();
    }

    private function validasi(array $data, User $user): void
    {
        $validator = Validator::make($data, [
            'unit_usaha_id' => ['required', 'exists:unit_usaha,id'],
            'metode_pembayaran_id' => ['required', 'exists:metode_pembayaran,id'],
            'periode_mulai' => ['required', 'date'],
            'periode_selesai' => ['required', 'date', 'after_or_equal:periode_mulai'],
            'pemasukan_ids' => ['required', 'array', 'min:1'],
            'pemasukan_ids.*' => ['integer'],
            'tanggal_diterima' => ['nullable', 'date'],
            'total_diterima' => ['nullable', 'integer', 'min:0'],
            'biaya_admin' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], [
            'pemasukan_ids.required' => 'Pilih minimal satu pemasukan untuk direkonsiliasi.',
            'periode_selesai.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
            'tanggal_diterima.required' => 'Tanggal dana diterima wajib diisi.',
        ]);

        $validator->after(function ($validator) use ($data, $user) {
            $unit = UnitUsaha::query()->find($data['unit_usaha_id'] ?? null);
            if ($unit === null || ! $unit->aktif) {
                $validator->errors()->add('unit_usaha_id', 'Unit usaha tidak aktif.');
            } elseif (! $user->bolehAksesUnit($unit->id)) {
                $validator->errors()->add('unit_usaha_id', 'Anda tidak memiliki akses ke unit usaha ini.');
            }

            $metode = MetodePembayaran::query()->find($data['metode_pembayaran_id'] ?? null);
            if ($metode === null || ! $metode->aktif) {
                $validator->errors()->add('metode_pembayaran_id', 'Metode pembayaran tidak aktif.');
            }

            if (! ($data['menunggu_settlement'] ?? false) && empty($data['tanggal_diterima'])) {
                $validator->errors()->add('tanggal_diterima', 'Tanggal dana diterima wajib diisi.');
            }

            $mulai = Carbon::parse($data['periode_mulai'] ?? now());
            $selesai = Carbon::parse($data['periode_selesai'] ?? now());
            if ($mulai->diffInDays($selesai) > 366) {
                $validator->errors()->add('periode_selesai', 'Rentang rekonsiliasi maksimal satu tahun.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @param  Collection<int, Pemasukan>  $pemasukan
     * @param  array<int, int>  $ids
     */
    private function pastikanKandidatValid(Collection $pemasukan, int $unitId, int $metodeId, string $mulai, string $selesai, array $ids): void
    {
        if ($pemasukan->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'pemasukan_ids' => 'Sebagian pemasukan yang dipilih sudah diverifikasi atau tidak lagi tersedia. Muat ulang halaman.',
            ]);
        }

        foreach ($pemasukan as $item) {
            $valid = $item->unit_usaha_id === $unitId
                && $item->metode_pembayaran_id === $metodeId
                && $item->tanggal->between(Carbon::parse($mulai), Carbon::parse($selesai))
                && $item->rekonsiliasi_id === null
                && in_array($item->status_verifikasi, [StatusVerifikasi::BELUM_DICEK, StatusVerifikasi::MENUNGGU_SETTLEMENT], true)
                && $item->deleted_at === null;

            if (! $valid) {
                throw ValidationException::withMessages([
                    'pemasukan_ids' => 'Pemasukan '.$item->note.' tidak valid untuk rekonsiliasi ini.',
                ]);
            }
        }
    }

    private function pastikanRentangTerbuka(string $mulai, string $selesai, int $unitId): void
    {
        $cursor = Carbon::parse($mulai)->startOfMonth();
        $akhir = Carbon::parse($selesai)->startOfMonth();

        while ($cursor->lte($akhir)) {
            $this->periode->pastikanTerbuka($cursor->copy()->startOfMonth(), $unitId);
            $cursor->addMonth();
        }
    }
}
