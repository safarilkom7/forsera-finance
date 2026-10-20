<?php

use App\Support\Format;

if (! function_exists('parse_rupiah')) {
    /**
     * Ubah input Rupiah (angka atau teks berpemisah) menjadi integer rupiah.
     */
    function parse_rupiah(mixed $nilai): int
    {
        if (is_int($nilai)) {
            return $nilai;
        }

        if (is_float($nilai)) {
            return (int) round($nilai);
        }

        $teks = trim((string) $nilai);

        if ($teks === '') {
            return 0;
        }

        $negatif = str_starts_with($teks, '-');

        // Format Indonesia: titik pemisah ribuan, koma desimal.
        if (str_contains($teks, ',')) {
            $teks = str_replace('.', '', $teks);
            $teks = str_replace(',', '.', $teks);
            $angka = (int) round((float) $teks);
        } else {
            $angka = (int) preg_replace('/\D+/', '', $teks);
        }

        return $negatif ? -$angka : $angka;
    }
}

if (! function_exists('format_rupiah')) {
    function format_rupiah(int|float|string|null $nilai, bool $denganPrefix = true): string
    {
        return Format::rupiah($nilai, $denganPrefix);
    }
}

if (! function_exists('format_tanggal')) {
    function format_tanggal(\Carbon\CarbonInterface|string|null $tanggal, bool $denganTahun = true): string
    {
        return Format::tanggal($tanggal, $denganTahun);
    }
}
