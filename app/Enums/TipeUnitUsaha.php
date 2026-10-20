<?php

namespace App\Enums;


enum TipeUnitUsaha: string
{
    case RESTO = 'RESTO';
    case KOPI_FRANCHISE = 'KOPI_FRANCHISE';
    case BARBERSHOP = 'BARBERSHOP';

    public function getLabel(): string
    {
        return match ($this) {
            self::RESTO => 'Restoran',
            self::KOPI_FRANCHISE => 'Kopi (Franchise)',
            self::BARBERSHOP => 'Barbershop',
        };
    }
}
