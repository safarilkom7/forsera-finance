<?php

namespace Database\Factories;

use App\Enums\StatusRekonsiliasi;
use App\Models\MetodePembayaran;
use App\Models\Rekonsiliasi;
use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rekonsiliasi>
 */
class RekonsiliasiFactory extends Factory
{
    protected $model = Rekonsiliasi::class;

    public function definition(): array
    {
        return [
            'kode' => 'REK-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
            'unit_usaha_id' => UnitUsaha::factory(),
            'metode_pembayaran_id' => MetodePembayaran::factory(),
            'periode_mulai' => now()->startOfMonth()->toDateString(),
            'periode_selesai' => now()->toDateString(),
            'tanggal_diterima' => now()->toDateString(),
            'total_dicatat' => 0,
            'potongan_kas' => 0,
            'biaya_admin' => 0,
            'total_diterima' => 0,
            'selisih' => 0,
            'status' => StatusRekonsiliasi::SESUAI,
            'catatan' => null,
            'bukti' => [],
            'diverifikasi_oleh' => User::factory(),
            'diverifikasi_at' => now(),
        ];
    }
}
