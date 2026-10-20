<?php

namespace App\Services;

use App\Enums\StatusInsiden;
use App\Enums\StatusPersetujuanPengeluaran;
use App\Models\Insiden;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Rekonsiliasi;
use App\Models\UnitUsaha;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LaporanService
{
    /**
     * @return array<int, array{unit: string, pemasukan: int, pengeluaran: int, laba: int, margin: float}>
     */
    public function labaRugi(Carbon $mulai, Carbon $selesai, ?int $unitId): array
    {
        $units = UnitUsaha::query()
            ->when($unitId, fn ($q) => $q->whereKey($unitId))
            ->orderBy('nama')
            ->get();

        $pemasukan = Pemasukan::query()
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->when($unitId, fn ($q) => $q->where('unit_usaha_id', $unitId))
            ->selectRaw('unit_usaha_id, SUM(nominal) as total')
            ->groupBy('unit_usaha_id')
            ->pluck('total', 'unit_usaha_id');

        $pengeluaran = Pengeluaran::query()
            ->dihitung()
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->when($unitId, fn ($q) => $q->where('unit_usaha_id', $unitId))
            ->selectRaw('unit_usaha_id, SUM(total_nominal) as total')
            ->groupBy('unit_usaha_id')
            ->pluck('total', 'unit_usaha_id');

        $rows = $units->map(function (UnitUsaha $unit) use ($pemasukan, $pengeluaran) {
            $masuk = (int) ($pemasukan[$unit->id] ?? 0);
            $keluar = (int) ($pengeluaran[$unit->id] ?? 0);
            $laba = $masuk - $keluar;

            return [
                'unit' => $unit->nama,
                'pemasukan' => $masuk,
                'pengeluaran' => $keluar,
                'laba' => $laba,
                'margin' => $masuk > 0 ? round($laba / $masuk * 100, 2) : 0.0,
            ];
        })->values()->all();

        $totalMasuk = (int) array_sum(array_column($rows, 'pemasukan'));
        $totalKeluar = (int) array_sum(array_column($rows, 'pengeluaran'));

        $rows[] = [
            'unit' => 'GABUNGAN',
            'pemasukan' => $totalMasuk,
            'pengeluaran' => $totalKeluar,
            'laba' => $totalMasuk - $totalKeluar,
            'margin' => $totalMasuk > 0 ? round(($totalMasuk - $totalKeluar) / $totalMasuk * 100, 2) : 0.0,
        ];

        return $rows;
    }

    /**
     * @return Collection<int, object>
     */
    public function pemasukanKategoriMetode(Carbon $mulai, Carbon $selesai, ?int $unitId): Collection
    {
        return Pemasukan::query()
            ->join('unit_usaha', 'unit_usaha.id', '=', 'pemasukan.unit_usaha_id')
            ->join('kategori_pemasukan', 'kategori_pemasukan.id', '=', 'pemasukan.kategori_pemasukan_id')
            ->join('metode_pembayaran', 'metode_pembayaran.id', '=', 'pemasukan.metode_pembayaran_id')
            ->whereBetween('pemasukan.tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->when($unitId, fn ($q) => $q->where('pemasukan.unit_usaha_id', $unitId))
            ->selectRaw('unit_usaha.nama as unit, kategori_pemasukan.nama as kategori, metode_pembayaran.nama as metode, COUNT(*) as transaksi, SUM(pemasukan.nominal) as total')
            ->groupBy('unit', 'kategori', 'metode')
            ->orderBy('unit')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function pengeluaranPerJenis(Carbon $mulai, Carbon $selesai, ?int $unitId): Collection
    {
        return Pengeluaran::query()
            ->join('jenis_pengeluaran', 'jenis_pengeluaran.id', '=', 'pengeluaran.jenis_pengeluaran_id')
            ->whereBetween('pengeluaran.tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->where('pengeluaran.status_persetujuan', StatusPersetujuanPengeluaran::DISETUJUI->value)
            ->when($unitId, fn ($q) => $q->where('pengeluaran.unit_usaha_id', $unitId))
            ->selectRaw('jenis_pengeluaran.nama as jenis, COUNT(*) as transaksi, SUM(pengeluaran.total_nominal) as total')
            ->groupBy('jenis')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function pengeluaranPerItem(Carbon $mulai, Carbon $selesai, ?int $unitId): Collection
    {
        return Pengeluaran::query()
            ->join('detail_pengeluaran', 'pengeluaran.id', '=', 'detail_pengeluaran.pengeluaran_id')
            ->join('item', 'item.id', '=', 'detail_pengeluaran.item_id')
            ->join('satuan', 'satuan.id', '=', 'detail_pengeluaran.satuan_id')
            ->whereBetween('pengeluaran.tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->where('pengeluaran.status_persetujuan', StatusPersetujuanPengeluaran::DISETUJUI->value)
            ->when($unitId, fn ($q) => $q->where('pengeluaran.unit_usaha_id', $unitId))
            ->selectRaw('item.nama as item, satuan.singkatan as satuan, SUM(detail_pengeluaran.jumlah) as jumlah, AVG(detail_pengeluaran.harga_satuan) as harga_rata, MIN(detail_pengeluaran.harga_satuan) as harga_min, MAX(detail_pengeluaran.harga_satuan) as harga_maks, SUM(detail_pengeluaran.subtotal) as total')
            ->groupBy('item', 'satuan')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * @return array{per_metode: Collection<int, object>, per_unit: Collection<int, object>, insiden: Collection<int, Insiden>}
     */
    public function rekonsiliasiRingkasan(Carbon $mulai, Carbon $selesai, ?int $unitId): array
    {
        $base = Rekonsiliasi::query()
            ->whereBetween('periode_mulai', [$mulai->toDateString(), $selesai->toDateString()])
            ->aktif()
            ->when($unitId, fn ($q) => $q->where('unit_usaha_id', $unitId));

        $perMetode = (clone $base)
            ->join('metode_pembayaran', 'metode_pembayaran.id', '=', 'rekonsiliasi.metode_pembayaran_id')
            ->selectRaw('metode_pembayaran.nama as metode, COUNT(*) as jumlah, SUM(rekonsiliasi.total_dicatat) as dicatat, SUM(rekonsiliasi.total_diterima) as diterima, SUM(rekonsiliasi.selisih) as selisih')
            ->groupBy('metode')
            ->orderBy('metode')
            ->get();

        $perUnit = (clone $base)
            ->join('unit_usaha', 'unit_usaha.id', '=', 'rekonsiliasi.unit_usaha_id')
            ->selectRaw('unit_usaha.nama as unit, COUNT(*) as jumlah, SUM(rekonsiliasi.selisih) as selisih, SUM(CASE WHEN rekonsiliasi.status = ? THEN 1 ELSE 0 END) as selisih_terbuka', [\App\Enums\StatusRekonsiliasi::SELISIH->value])
            ->groupBy('unit')
            ->orderBy('unit')
            ->get();

        $insiden = Insiden::query()
            ->whereHas('rekonsiliasi', fn ($q) => $q
                ->whereBetween('periode_mulai', [$mulai->toDateString(), $selesai->toDateString()])
                ->when($unitId, fn ($r) => $r->where('unit_usaha_id', $unitId)))
            ->with(['rekonsiliasi.unitUsaha'])
            ->orderBy('created_at')
            ->get();

        return [
            'per_metode' => $perMetode,
            'per_unit' => $perUnit,
            'insiden' => $insiden,
        ];
    }

    /**
     * Buku kas harian per unit dengan saldo berjalan relatif terhadap awal periode.
     *
     * @return array<int, array{tanggal: string, keterangan: string, masuk: int, keluar: int, saldo: int}>
     */
    public function bukuKas(Carbon $mulai, Carbon $selesai, int $unitId): array
    {
        $pemasukan = Pemasukan::query()
            ->with(['kategori', 'metode'])
            ->where('unit_usaha_id', $unitId)
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->map(fn (Pemasukan $p) => [
                'tanggal' => $p->tanggal->toDateString(),
                'keterangan' => 'Pemasukan '.$p->kategori->nama.' ('.$p->metode->nama.')'.($p->note ? ' — '.$p->note : ''),
                'masuk' => (int) $p->nominal,
                'keluar' => 0,
                'waktu' => $p->created_at?->timestamp ?? 0,
            ]);

        $pengeluaran = Pengeluaran::query()
            ->with(['jenis', 'metode'])
            ->dihitung()
            ->where('unit_usaha_id', $unitId)
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->map(fn (Pengeluaran $p) => [
                'tanggal' => $p->tanggal->toDateString(),
                'keterangan' => 'Pengeluaran '.$p->jenis->nama.' ('.$p->metode->nama.')'.($p->note ? ' — '.$p->note : ''),
                'masuk' => 0,
                'keluar' => (int) $p->total_nominal,
                'waktu' => $p->created_at?->timestamp ?? 0,
            ]);

        $saldo = 0;

        return $pemasukan->concat($pengeluaran)
            ->sortBy([['tanggal', 'asc'], ['waktu', 'asc']])
            ->values()
            ->map(function (array $baris) use (&$saldo) {
                $saldo += $baris['masuk'] - $baris['keluar'];
                unset($baris['waktu']);

                return $baris + ['saldo' => $saldo];
            })
            ->all();
    }
}
