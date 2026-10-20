<?php

namespace App\Enums;


enum StatusVerifikasi: string
{
    case BELUM_DICEK = 'BELUM_DICEK';
    case MENUNGGU_SETTLEMENT = 'MENUNGGU_SETTLEMENT';
    case SESUAI = 'SESUAI';
    case SELISIH = 'SELISIH';
    case SELESAI = 'SELESAI';

    public function getLabel(): string
    {
        return match ($this) {
            self::BELUM_DICEK => 'Belum Dicek',
            self::MENUNGGU_SETTLEMENT => 'Menunggu Settlement',
            self::SESUAI => 'Sesuai',
            self::SELISIH => 'Selisih',
            self::SELESAI => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BELUM_DICEK => 'gray',
            self::MENUNGGU_SETTLEMENT => 'warning',
            self::SESUAI => 'success',
            self::SELISIH => 'danger',
            self::SELESAI => 'info',
        };
    }

    /** Status yang mengunci data dari perubahan biasa. */
    public function terkunci(): bool
    {
        return $this !== self::BELUM_DICEK;
    }

    public function terverifikasi(): bool
    {
        return in_array($this, [self::SESUAI, self::SELISIH, self::SELESAI], true);
    }
}
