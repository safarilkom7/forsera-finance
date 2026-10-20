<?php

namespace App\Enums;


enum StatusRekonsiliasi: string
{
    case MENUNGGU_SETTLEMENT = 'MENUNGGU_SETTLEMENT';
    case SESUAI = 'SESUAI';
    case SELISIH = 'SELISIH';
    case SELESAI = 'SELESAI';
    case DIBATALKAN = 'DIBATALKAN';

    public function getLabel(): string
    {
        return match ($this) {
            self::MENUNGGU_SETTLEMENT => 'Menunggu Settlement',
            self::SESUAI => 'Sesuai',
            self::SELISIH => 'Selisih',
            self::SELESAI => 'Selesai',
            self::DIBATALKAN => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MENUNGGU_SETTLEMENT => 'warning',
            self::SESUAI => 'success',
            self::SELISIH => 'danger',
            self::SELESAI => 'info',
            self::DIBATALKAN => 'gray',
        };
    }

    public function aktif(): bool
    {
        return $this !== self::DIBATALKAN;
    }
}
