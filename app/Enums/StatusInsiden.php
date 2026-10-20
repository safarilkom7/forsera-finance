<?php

namespace App\Enums;


enum StatusInsiden: string
{
    case OPEN = 'OPEN';
    case INVESTIGASI = 'INVESTIGASI';
    case SELESAI = 'SELESAI';
    case DIHAPUSKAN = 'DIHAPUSKAN';
    case DIBATALKAN = 'DIBATALKAN';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => 'Terbuka',
            self::INVESTIGASI => 'Investigasi',
            self::SELESAI => 'Selesai',
            self::DIHAPUSKAN => 'Dihapuskan',
            self::DIBATALKAN => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPEN => 'danger',
            self::INVESTIGASI => 'warning',
            self::SELESAI => 'success',
            self::DIHAPUSKAN => 'gray',
            self::DIBATALKAN => 'gray',
        };
    }

    public function terbuka(): bool
    {
        return in_array($this, [self::OPEN, self::INVESTIGASI], true);
    }

    public function final(): bool
    {
        return in_array($this, [self::SELESAI, self::DIHAPUSKAN, self::DIBATALKAN], true);
    }
}
