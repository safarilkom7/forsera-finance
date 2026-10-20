<?php

namespace App\Enums;


enum TipeMetodePembayaran: string
{
    case TUNAI = 'TUNAI';
    case QRIS = 'QRIS';
    case TRANSFER = 'TRANSFER';

    public function getLabel(): string
    {
        return match ($this) {
            self::TUNAI => 'Tunai',
            self::QRIS => 'QRIS',
            self::TRANSFER => 'Transfer',
        };
    }

    public function nonTunai(): bool
    {
        return $this !== self::TUNAI;
    }
}
