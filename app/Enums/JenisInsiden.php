<?php

namespace App\Enums;


enum JenisInsiden: string
{
    case KURANG_SETOR = 'KURANG_SETOR';
    case LEBIH_SETOR = 'LEBIH_SETOR';
    case SALAH_INPUT = 'SALAH_INPUT';
    case PEMBAYARAN_DOBEL = 'PEMBAYARAN_DOBEL';
    case SELISIH_BIAYA_ADMIN = 'SELISIH_BIAYA_ADMIN';
    case DANA_BELUM_CAIR = 'DANA_BELUM_CAIR';
    case LAINNYA = 'LAINNYA';

    public function getLabel(): string
    {
        return match ($this) {
            self::KURANG_SETOR => 'Kurang Setor',
            self::LEBIH_SETOR => 'Lebih Setor',
            self::SALAH_INPUT => 'Salah Input',
            self::PEMBAYARAN_DOBEL => 'Pembayaran Dobel',
            self::SELISIH_BIAYA_ADMIN => 'Selisih Biaya Admin',
            self::DANA_BELUM_CAIR => 'Dana Belum Cair',
            self::LAINNYA => 'Lainnya',
        };
    }

    public static function saran(ArahSelisih $arah): self
    {
        return $arah === ArahSelisih::KURANG
            ? self::KURANG_SETOR
            : self::LEBIH_SETOR;
    }
}
