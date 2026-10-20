<?php

namespace Database\Factories;

use App\Enums\TipeUnitUsaha;
use App\Models\UnitUsaha;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitUsahaFactory extends Factory
{
    protected $model = UnitUsaha::class;

    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->lexify('U??')),
            'nama' => 'Unit '.fake()->unique()->company(),
            'tipe' => fake()->randomElement(TipeUnitUsaha::cases()),
            'aktif' => true,
        ];
    }
}
