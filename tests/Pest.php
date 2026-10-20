<?php

use App\Models\KategoriPemasukan;
use App\Models\MetodePembayaran;
use App\Models\UnitUsaha;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

/**
 * Seed peran, master data, dan pengaturan default sekali per test.
 */
function siapkanDataDasar(): void
{
    test()->seed(DatabaseSeeder::class);
}

/**
 * @param  array<int, UnitUsaha>  $units
 */
function pengguna(string $peran, array $units = []): User
{
    if (! Role::query()->where('name', $peran)->exists()) {
        siapkanDataDasar();
    }

    $user = User::factory()->create([
        'must_change_password' => false,
        'aktif' => true,
    ]);

    $user->assignRole($peran);

    foreach ($units as $unit) {
        $user->unitUsaha()->attach($unit->id);
    }

    return $user->refresh();
}

/**
 * @return array{resto: UnitUsaha, kopi: UnitUsaha, tunai: MetodePembayaran, qris: MetodePembayaran, transfer: MetodePembayaran, kategori_resto: KategoriPemasukan, kategori_kopi: KategoriPemasukan}
 */
function masterDasar(): array
{
    siapkanDataDasar();

    $resto = UnitUsaha::query()->where('kode', 'FRS')->firstOrFail();
    $kopi = UnitUsaha::query()->where('kode', 'KAR')->firstOrFail();

    return [
        'resto' => $resto,
        'kopi' => $kopi,
        'tunai' => MetodePembayaran::query()->where('nama', 'Tunai')->firstOrFail(),
        'qris' => MetodePembayaran::query()->where('nama', 'QRIS')->firstOrFail(),
        'transfer' => MetodePembayaran::query()->where('nama', 'Transfer Bank')->firstOrFail(),
        'kategori_resto' => KategoriPemasukan::query()->where('unit_usaha_id', $resto->id)->firstOrFail(),
        'kategori_kopi' => KategoriPemasukan::query()->where('unit_usaha_id', $kopi->id)->firstOrFail(),
    ];
}
