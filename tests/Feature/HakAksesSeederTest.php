<?php

use App\Models\User;
use Database\Seeders\HakAksesSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('memperbaiki state izin database lama: izin.kelola untuk owner, audit.lihat untuk admin, hapus dicabut dari admin/staf', function () {
    // Simulasi DB production lama: izin.kelola belum ada, admin/staf masih punya izin hapus.
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::findOrCreate('audit.lihat', 'web');
    Permission::findOrCreate('pemasukan.hapus', 'web');
    Permission::findOrCreate('pengeluaran.hapus', 'web');

    Role::findOrCreate(User::ROLE_OWNER, 'web');
    Role::findOrCreate(User::ROLE_ADMIN, 'web');
    Role::findOrCreate(User::ROLE_STAF, 'web');

    Role::query()->where('name', User::ROLE_ADMIN)->first()
        ->givePermissionTo(['audit.lihat', 'pemasukan.hapus', 'pengeluaran.hapus']);
    Role::query()->where('name', User::ROLE_STAF)->first()
        ->givePermissionTo(['pemasukan.hapus', 'pengeluaran.hapus']);

    $this->seed(HakAksesSeeder::class);

    $owner = Role::query()->where('name', User::ROLE_OWNER)->first();
    $admin = Role::query()->where('name', User::ROLE_ADMIN)->first();
    $staf = Role::query()->where('name', User::ROLE_STAF)->first();

    expect($owner->hasPermissionTo('izin.kelola'))->toBeTrue()
        ->and($admin->hasPermissionTo('audit.lihat'))->toBeTrue()
        ->and($admin->hasPermissionTo('pemasukan.hapus'))->toBeFalse()
        ->and($admin->hasPermissionTo('pengeluaran.hapus'))->toBeFalse()
        ->and($staf->hasPermissionTo('pemasukan.hapus'))->toBeFalse()
        ->and($staf->hasPermissionTo('pengeluaran.hapus'))->toBeFalse();
});

it('idempoten: aman dijalankan lagi pada database yang sudah memakai matriks baru', function () {
    siapkanDataDasar();

    $this->seed(HakAksesSeeder::class);

    $owner = Role::query()->where('name', User::ROLE_OWNER)->first();
    $admin = Role::query()->where('name', User::ROLE_ADMIN)->first();
    $staf = Role::query()->where('name', User::ROLE_STAF)->first();

    expect($owner->hasPermissionTo('izin.kelola'))->toBeTrue()
        ->and($admin->hasPermissionTo('audit.lihat'))->toBeTrue()
        ->and($admin->hasPermissionTo('rekonsiliasi.lihat'))->toBeTrue()
        ->and($admin->hasPermissionTo('pemasukan.hapus'))->toBeFalse()
        ->and($staf->hasPermissionTo('pemasukan.hapus'))->toBeFalse()
        ->and($staf->hasPermissionTo('pemasukan.input'))->toBeTrue();
});
