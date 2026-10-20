<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\JenisPengeluaran;
use App\Models\Satuan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'nama' => 'Item '.fake()->unique()->words(2, true),
            'satuan_default_id' => Satuan::factory(),
            'jenis_pengeluaran_id' => JenisPengeluaran::factory(),
            'harga_terakhir' => fake()->numberBetween(5_000, 200_000),
            'aktif' => true,
        ];
    }
}
