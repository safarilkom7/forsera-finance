<?php

namespace App\Enums;


enum StatusPersetujuanPengeluaran: string
{
    case DISETUJUI = 'DISETUJUI';
    case MENUNGGU = 'MENUNGGU';
    case DITOLAK = 'DITOLAK';

    public function getLabel(): string
    {
        return match ($this) {
            self::DISETUJUI => 'Disetujui',
            self::MENUNGGU => 'Menunggu Persetujuan',
            self::DITOLAK => 'Ditolak',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DISETUJUI => 'success',
            self::MENUNGGU => 'warning',
            self::DITOLAK => 'danger',
        };
    }

    public function dihitung(): bool
    {
        return $this === self::DISETUJUI;
    }
}
