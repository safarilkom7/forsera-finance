<?php

namespace Database\Factories;

use App\Models\KategoriPemasukan;
use App\Models\UnitUsaha;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriPemasukan>
 */
class KategoriPemasukanFactory extends Factory
{
    protected $model = KategoriPemasukan::class;

    public function definition(): array
    {
        return [
            'unit_usaha_id' => UnitUsaha::factory(),
            'nama' => 'Kategori '.fake()->unique()->words(2, true),
            'aktif' => true,
        ];
    }

    public function umum(): static
    {
        return $this->state(['unit_usaha_id' => null]);
    }
}
