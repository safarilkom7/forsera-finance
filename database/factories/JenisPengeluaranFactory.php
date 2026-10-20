<?php

namespace Database\Factories;

use App\Models\JenisPengeluaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisPengeluaran>
 */
class JenisPengeluaranFactory extends Factory
{
    protected $model = JenisPengeluaran::class;

    public function definition(): array
    {
        return [
            'nama' => 'Jenis '.fake()->unique()->words(2, true),
            'aktif' => true,
        ];
    }
}
