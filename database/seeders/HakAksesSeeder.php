<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder terpisah untuk fitur Hak Akses (izin khusus per pengguna), agar
 * perubahan izin bisa diterapkan pada database production yang sudah berjalan
 * tanpa menjalankan seluruh DatabaseSeeder.
 *
 * Yang dilakukan:
 * 1. Membuat izin baru `izin.kelola` dan memberikannya ke Owner (menu Hak Akses).
 * 2. Memberi `audit.lihat` ke Admin Keuangan — halaman log aktivitas kini
 *    dilindungi `permission:audit.lihat`, jadi akses lamanya dipertahankan.
 * 3. Mencabut `pemasukan.hapus` dan `pengeluaran.hapus` dari default peran
 *    Admin Keuangan dan Staf Unit — penghapusan transaksi kini diperiksa
 *    policy lewat izin tersebut. Default tetap hanya Owner yang bisa menghapus;
 *    izin hapus bisa diberikan ke pengguna tertentu lewat menu Hak Akses.
 *
 * Aman dijalankan berulang (idempoten): hanya menambah/mencabut izin peran dan
 * tidak menyentuh izin khusus per pengguna di model_has_permissions.
 */
class HakAksesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $owner = Role::findOrCreate(User::ROLE_OWNER, 'web');
        $admin = Role::findOrCreate(User::ROLE_ADMIN, 'web');
        $staf = Role::findOrCreate(User::ROLE_STAF, 'web');

        Permission::findOrCreate('izin.kelola', 'web');

        // Menu Hak Akses: hanya Owner.
        $owner->givePermissionTo('izin.kelola');

        // Log aktivitas kini dilindungi permission:audit.lihat.
        $admin->givePermissionTo('audit.lihat');

        // Cabut izin hapus dari default admin/staf bila masih ada (DB lama).
        foreach (['pemasukan.hapus', 'pengeluaran.hapus'] as $nama) {
            $izin = Permission::query()->where('name', $nama)->where('guard_name', 'web')->first();

            if ($izin !== null) {
                $admin->revokePermissionTo($izin);
                $staf->revokePermissionTo($izin);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
