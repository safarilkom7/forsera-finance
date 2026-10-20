<?php

namespace App\Services;

use App\Support\Format;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Periode akuntansi Forsera Group.
 *
 * Default mulai tanggal 5 dan berakhir sehari sebelum tanggal 5 bulan berikutnya
 * (5 Okt s/d 4 Nov), sehingga gajian tanggal 6 jatuh tepat setelah periode ditutup.
 * Tanggal mulai bisa diubah Owner/Admin lewat Pengaturan (`tanggal_mulai_periode`).
 *
 * Label periode selalu memakai bulan awal, mis. transaksi 3 Nov masuk periode "November 2026"
 * bila tanggal mulai 1, tetapi masuk "Oktober 2026" bila tanggal mulai 5.
 */
class PeriodeAkuntansiService
{
    public function __construct(private readonly PengaturanService $pengaturan) {}

    public function tanggalMulai(): int
    {
        return max(1, min(28, $this->pengaturan->getInt('tanggal_mulai_periode', 5)));
    }

    /**
     * @return array{tahun: int, bulan: int, mulai: Carbon, selesai: Carbon, label: string, rentang: string}
     */
    public function untukTanggal(CarbonInterface|string $tanggal): array
    {
        $carbon = Carbon::parse($tanggal)->startOfDay();

        if ($carbon->day < $this->tanggalMulai()) {
            $carbon->subMonthNoOverflow();
        }

        return $this->untukPeriode((int) $carbon->year, (int) $carbon->month);
    }

    /**
     * @return array{tahun: int, bulan: int, mulai: Carbon, selesai: Carbon, label: string, rentang: string}
     */
    public function untukPeriode(int $tahun, int $bulan): array
    {
        $mulai = Carbon::create($tahun, $bulan, $this->tanggalMulai())->startOfDay();
        $selesai = $mulai->copy()->addMonthNoOverflow()->subDay()->endOfDay();

        return [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'label' => Format::periode($tahun, $bulan),
            'rentang' => Format::tanggal($mulai).' – '.Format::tanggal($selesai),
        ];
    }

    /**
     * @return array{tahun: int, bulan: int, mulai: Carbon, selesai: Carbon, label: string, rentang: string}
     */
    public function berjalan(?CarbonInterface $acuan = null): array
    {
        return $this->untukTanggal($acuan ?? now());
    }

    /**
     * @return array{tahun: int, bulan: int, mulai: Carbon, selesai: Carbon, label: string, rentang: string}
     */
    public function sebelumnya(?CarbonInterface $acuan = null): array
    {
        $berjalan = $this->berjalan($acuan);

        return $this->untukTanggal($berjalan['mulai']->copy()->subDay());
    }

    /**
     * Daftar periode terakhir (termasuk periode yang sedang berjalan), terbaru lebih dulu.
     *
     * @return array<int, array{tahun: int, bulan: int, kunci: string, label: string, rentang: string, berjalan: bool}>
     */
    public function daftar(int $jumlah = 24, ?CarbonInterface $acuan = null): array
    {
        $hasil = [];
        $periode = $this->berjalan($acuan);

        for ($i = 0; $i < $jumlah; $i++) {
            $hasil[] = [
                'tahun' => $periode['tahun'],
                'bulan' => $periode['bulan'],
                'kunci' => sprintf('%04d-%02d', $periode['tahun'], $periode['bulan']),
                'label' => $periode['label'],
                'rentang' => $periode['rentang'],
                'berjalan' => $i === 0,
            ];

            $periode = $this->untukTanggal($periode['mulai']->copy()->subDay());
        }

        return $hasil;
    }
}
