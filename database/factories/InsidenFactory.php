<?php

namespace Database\Factories;

use App\Enums\ArahSelisih;
use App\Enums\JenisInsiden;
use App\Enums\StatusInsiden;
use App\Models\Insiden;
use App\Models\Rekonsiliasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insiden>
 */
class InsidenFactory extends Factory
{
    protected $model = Insiden::class;

    public function definition(): array
    {
        return [
            'kode' => 'INC-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
            'rekonsiliasi_id' => Rekonsiliasi::factory(),
            'jenis' => JenisInsiden::KURANG_SETOR,
            'arah' => ArahSelisih::KURANG,
            'nominal_selisih' => 50_000,
            'nominal_terselesaikan' => 0,
            'status' => StatusInsiden::OPEN,
            'dibuat_oleh' => User::factory(),
        ];
    }
}
