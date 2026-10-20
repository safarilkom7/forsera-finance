<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class Format
{
    public static function rupiah(int|float|string|null $nilai, bool $denganPrefix = true): string
    {
        $angka = (int) round((float) ($nilai ?? 0));
        $teks = number_format($angka, 0, ',', '.');

        return $denganPrefix ? 'Rp '.$teks : $teks;
    }

    public static function rupiahSigned(int|float|string|null $nilai): string
    {
        $angka = (int) round((float) ($nilai ?? 0));
        $tanda = $angka < 0 ? '−' : ($angka > 0 ? '+' : '');

        return $tanda.'Rp '.number_format(abs($angka), 0, ',', '.');
    }

    public static function tanggal(CarbonInterface|string|null $tanggal, bool $denganTahun = true): string
    {
        if ($tanggal === null) {
            return '-';
        }

        $carbon = $tanggal instanceof CarbonInterface ? $tanggal : Carbon::parse($tanggal)->setTimezone(config('app.timezone'));

        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][$carbon->month - 1];

        return $carbon->day.' '.$bulan.($denganTahun ? ' '.$carbon->year : '');
    }

    public static function tanggalWaktu(CarbonInterface|string|null $tanggal): string
    {
        if ($tanggal === null) {
            return '-';
        }

        $carbon = $tanggal instanceof CarbonInterface ? $tanggal : Carbon::parse($tanggal)->setTimezone(config('app.timezone'));

        return self::tanggal($carbon).' '.$carbon->format('H:i');
    }

    public static function persen(float|int|null $nilai, int $desimal = 1): string
    {
        return number_format((float) $nilai, $desimal, ',', '.').'%';
    }

    public static function angka(float|int|string|null $nilai, int $desimal = 3): string
    {
        return rtrim(rtrim(number_format((float) $nilai, $desimal, ',', '.'), '0'), ',');
    }

    public static function periode(int $tahun, int $bulan): string
    {
        $bulanNama = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$bulan - 1];

        return $bulanNama.' '.$tahun;
    }
}
