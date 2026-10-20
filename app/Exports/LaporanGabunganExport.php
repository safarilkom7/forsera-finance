<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanGabunganExport implements WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    public function sheets(): array
    {
        $laba = collect($this->data['laba_rugi'])->map(fn ($r) => [
            $r['unit'], $r['pemasukan'], $r['pengeluaran'], $r['laba'], $r['margin'],
        ])->all();

        $kategori = collect($this->data['pemasukan_kategori'])->map(fn ($r) => [
            $r->unit, $r->kategori, $r->metode, (int) $r->transaksi, (int) $r->total,
        ])->all();

        $jenis = collect($this->data['pengeluaran_jenis'])->map(fn ($r) => [
            $r->jenis, (int) $r->transaksi, (int) $r->total,
        ])->all();

        $item = collect($this->data['pengeluaran_item'])->map(fn ($r) => [
            $r->item, $r->satuan, (float) $r->jumlah, round((float) $r->harga_rata, 2),
            (int) $r->harga_min, (int) $r->harga_maks, (int) $r->total,
        ])->all();

        $rekMetode = collect($this->data['rekonsiliasi']['per_metode'])->map(fn ($r) => [
            $r->metode, (int) $r->jumlah, (int) $r->dicatat, (int) $r->diterima, (int) $r->selisih,
        ])->all();

        $rekUnit = collect($this->data['rekonsiliasi']['per_unit'])->map(fn ($r) => [
            $r->unit, (int) $r->jumlah, (int) $r->selisih, (int) $r->selisih_terbuka,
        ])->all();

        $insiden = collect($this->data['rekonsiliasi']['insiden'])->map(fn ($r) => [
            $r->kode, $r->rekonsiliasi?->unitUsaha?->nama, $r->jenis?->getLabel(), $r->arah?->getLabel(),
            (int) $r->nominal_selisih, (int) $r->nominal_terselesaikan, $r->status?->getLabel(),
            $r->tipe_penyelesaian?->getLabel(),
        ])->all();

        $bukuKas = [];
        foreach ($this->data['buku_kas'] as $unit) {
            foreach ($unit['baris'] as $baris) {
                $bukuKas[] = [$unit['unit']->nama, $baris['tanggal'], $baris['keterangan'], $baris['masuk'], $baris['keluar'], $baris['saldo']];
            }
        }

        return [
            new ArrayExport('Laba Rugi', ['Unit', 'Pemasukan', 'Pengeluaran', 'Laba Bersih', 'Margin %'], $laba),
            new ArrayExport('Pemasukan Kategori', ['Unit', 'Kategori', 'Metode', 'Transaksi', 'Total'], $kategori),
            new ArrayExport('Pengeluaran per Jenis', ['Jenis', 'Transaksi', 'Total'], $jenis),
            new ArrayExport('Pengeluaran per Item', ['Item', 'Satuan', 'Jumlah', 'Harga Rata-rata', 'Harga Terendah', 'Harga Tertinggi', 'Total'], $item),
            new ArrayExport('Rekonsiliasi per Metode', ['Metode', 'Jumlah', 'Dicatat', 'Diterima', 'Selisih'], $rekMetode),
            new ArrayExport('Rekonsiliasi per Unit', ['Unit', 'Jumlah', 'Selisih', 'Selisih Terbuka'], $rekUnit),
            new ArrayExport('Insiden', ['Kode', 'Unit', 'Jenis', 'Arah', 'Selisih', 'Terselesaikan', 'Status', 'Penyelesaian'], $insiden),
            new ArrayExport('Buku Kas', ['Unit', 'Tanggal', 'Keterangan', 'Masuk', 'Keluar', 'Saldo'], $bukuKas),
        ];
    }
}
