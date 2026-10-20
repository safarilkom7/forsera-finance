<?php

namespace Database\Factories;

use App\Enums\StatusVerifikasi;
use App\Models\KategoriPemasukan;
use App\Models\MetodePembayaran;
use App\Models\Pemasukan;
use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pemasukan>
 */
class PemasukanFactory extends Factory
{
    protected $model = Pemasukan::class;

    public function definition(): array
    {
        $unit = UnitUsaha::factory();

        return [
            'tanggal' => now()->toDateString(),
            'unit_usaha_id' => $unit,
            'kategori_pemasukan_id' => KategoriPemasukan::factory()->state(fn () => ['unit_usaha_id' => null]),
            'metode_pembayaran_id' => MetodePembayaran::factory(),
            'nominal' => fake()->numberBetween(50_000, 2_000_000),
            'note' => null,
            'lampiran' => [],
            'status_verifikasi' => StatusVerifikasi::BELUM_DICEK,
            'dibuat_oleh' => User::factory(),
        ];
    }
}
