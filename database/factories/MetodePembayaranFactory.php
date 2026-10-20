<?php

namespace Database\Factories;

use App\Enums\TipeMetodePembayaran;
use App\Models\MetodePembayaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetodePembayaran>
 */
class MetodePembayaranFactory extends Factory
{
    protected $model = MetodePembayaran::class;

    public function definition(): array
    {
        return [
            'nama' => 'Metode '.fake()->unique()->word(),
            'tipe' => fake()->randomElement(TipeMetodePembayaran::cases()),
            'aktif' => true,
        ];
    }

    public function tunai(): static
    {
        return $this->state(['nama' => 'Tunai '.fake()->unique()->numerify('###'), 'tipe' => TipeMetodePembayaran::TUNAI]);
    }

    public function qris(): static
    {
        return $this->state(['nama' => 'QRIS '.fake()->unique()->numerify('###'), 'tipe' => TipeMetodePembayaran::QRIS]);
    }

    public function transfer(): static
    {
        return $this->state(['nama' => 'Transfer '.fake()->unique()->numerify('###'), 'tipe' => TipeMetodePembayaran::TRANSFER]);
    }
}
