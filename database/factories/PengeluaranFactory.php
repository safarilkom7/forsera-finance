<?php

namespace Database\Factories;

use App\Enums\StatusPersetujuanPengeluaran;
use App\Models\JenisPengeluaran;
use App\Models\MetodePembayaran;
use App\Models\Pengeluaran;
use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengeluaran>
 */
class PengeluaranFactory extends Factory
{
    protected $model = Pengeluaran::class;

    public function definition(): array
    {
        return [
            'tanggal' => now()->toDateString(),
            'unit_usaha_id' => UnitUsaha::factory(),
            'jenis_pengeluaran_id' => JenisPengeluaran::factory(),
            'metode_pembayaran_id' => MetodePembayaran::factory(),
            'total_nominal' => fake()->numberBetween(10_000, 500_000),
            'dari_kas_harian' => false,
            'note' => null,
            'lampiran' => [],
            'status_persetujuan' => StatusPersetujuanPengeluaran::DISETUJUI,
            'dibuat_oleh' => User::factory(),
        ];
    }

    public function menunggu(): static
    {
        return $this->state([
            'status_persetujuan' => StatusPersetujuanPengeluaran::MENUNGGU,
            'disetujui_oleh' => null,
            'disetujui_at' => null,
        ]);
    }

    public function kasHarian(): static
    {
        return $this->state(['dari_kas_harian' => true]);
    }
}
