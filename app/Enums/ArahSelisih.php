<?php

namespace App\Enums;


enum ArahSelisih: string
{
    case KURANG = 'KURANG';
    case LEBIH = 'LEBIH';

    public function getLabel(): string
    {
        return match ($this) {
            self::KURANG => 'Kurang',
            self::LEBIH => 'Lebih',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::KURANG => 'danger',
            self::LEBIH => 'warning',
        };
    }

    public static function dari(int $selisih): self
    {
        return $selisih < 0 ? self::KURANG : self::LEBIH;
    }
}
