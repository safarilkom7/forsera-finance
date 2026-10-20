<?php

namespace App\Enums;


enum TipePenyelesaian: string
{
    case KOREKSI_DATA = 'KOREKSI_DATA';
    case SETORAN_SUSULAN = 'SETORAN_SUSULAN';
    case DANA_CAIR_SUSULAN = 'DANA_CAIR_SUSULAN';
    case GANTI_RUGI = 'GANTI_RUGI';
    case CATAT_PENDAPATAN_LAIN = 'CATAT_PENDAPATAN_LAIN';
    case WRITE_OFF = 'WRITE_OFF';

    public function getLabel(): string
    {
        return match ($this) {
            self::KOREKSI_DATA => 'Koreksi Data',
            self::SETORAN_SUSULAN => 'Setoran Susulan',
            self::DANA_CAIR_SUSULAN => 'Dana Cair Susulan',
            self::GANTI_RUGI => 'Ganti Rugi',
            self::CATAT_PENDAPATAN_LAIN => 'Catat Pendapatan Lain',
            self::WRITE_OFF => 'Write-off (Hapus Selisih)',
        };
    }

    /** Penyelesaian yang memerlukan pencatatan nominal penerimaan bertahap. */
    public function butuhNominal(): bool
    {
        return in_array($this, [
            self::SETORAN_SUSULAN,
            self::DANA_CAIR_SUSULAN,
            self::GANTI_RUGI,
        ], true);
    }

    /** Penyelesaian yang langsung menutup insiden tanpa nominal tambahan. */
    public function langsungSelesai(): bool
    {
        return in_array($this, [
            self::KOREKSI_DATA,
            self::CATAT_PENDAPATAN_LAIN,
            self::WRITE_OFF,
        ], true);
    }
}
