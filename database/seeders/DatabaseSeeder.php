<?php

namespace Database\Seeders;

use App\Enums\TipeMetodePembayaran;
use App\Enums\TipeUnitUsaha;
use App\Models\Item;
use App\Models\JenisPengeluaran;
use App\Models\KategoriPemasukan;
use App\Models\MetodePembayaran;
use App\Models\Satuan;
use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\BrandingService;
use App\Services\PengaturanService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /** Password awal semua akun contoh (wajib diganti setelah login pertama). */
    public const PASSWORD_DEMO = 'Forsera#2026';

    public function run(): void
    {
        DB::transaction(function () {
            $this->seedPeran();
            $this->seedMaster();
            app(PengaturanService::class)->pastikanDefault();
            app(BrandingService::class)->pastikanDefault();
            $this->seedOwner();

            if (app()->environment('local')) {
                $this->seedAkunDemo();
            }
        });
    }

    private function seedPeran(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $izin = [
            'dashboard.lihat',
            'pemasukan.lihat',
            'pemasukan.input',
            'pemasukan.ubah',
            'pemasukan.hapus',
            'pengeluaran.lihat',
            'pengeluaran.input',
            'pengeluaran.ubah',
            'pengeluaran.hapus',
            'pengeluaran.setujui',
            'rekonsiliasi.lihat',
            'rekonsiliasi.verifikasi',
            'insiden.lihat',
            'insiden.kelola',
            'laporan.lihat',
            'master.kelola',
            'pengguna.kelola',
            'pengaturan.kelola',
            'periode.kelola',
            'audit.lihat',
            'koreksi.terkunci',
            'write_off',
        ];

        foreach ($izin as $nama) {
            Permission::findOrCreate($nama, 'web');
        }

        $owner = Role::findOrCreate(User::ROLE_OWNER, 'web');
        $admin = Role::findOrCreate(User::ROLE_ADMIN, 'web');
        $staf = Role::findOrCreate(User::ROLE_STAF, 'web');

        $owner->syncPermissions($izin);

        $admin->syncPermissions([
            'dashboard.lihat', 'pemasukan.lihat', 'pemasukan.input', 'pemasukan.ubah', 'pemasukan.hapus',
            'pengeluaran.lihat', 'pengeluaran.input', 'pengeluaran.ubah', 'pengeluaran.hapus',
            'rekonsiliasi.lihat', 'rekonsiliasi.verifikasi', 'insiden.lihat', 'insiden.kelola',
            'laporan.lihat', 'master.kelola',
        ]);

        $staf->syncPermissions([
            'dashboard.lihat', 'pemasukan.lihat', 'pemasukan.input', 'pemasukan.ubah', 'pemasukan.hapus',
            'pengeluaran.lihat', 'pengeluaran.input', 'pengeluaran.ubah', 'pengeluaran.hapus',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedMaster(): void
    {
        $resto = UnitUsaha::query()->firstOrCreate(
            ['kode' => 'FRS'],
            ['nama' => 'FORSERA RESTO', 'tipe' => TipeUnitUsaha::RESTO, 'aktif' => true],
        );

        $kopi = UnitUsaha::query()->firstOrCreate(
            ['kode' => 'KAR'],
            ['nama' => 'KOPI AROBI', 'tipe' => TipeUnitUsaha::KOPI_FRANCHISE, 'aktif' => true],
        );

        $barber = UnitUsaha::query()->firstOrCreate(
            ['kode' => 'FBS'],
            ['nama' => 'FORSERA BARBERSHOP', 'tipe' => TipeUnitUsaha::BARBERSHOP, 'aktif' => true],
        );

        $metode = [
            ['nama' => 'Tunai', 'tipe' => TipeMetodePembayaran::TUNAI],
            ['nama' => 'QRIS', 'tipe' => TipeMetodePembayaran::QRIS],
            ['nama' => 'Transfer Bank', 'tipe' => TipeMetodePembayaran::TRANSFER],
        ];

        foreach ($metode as $m) {
            MetodePembayaran::query()->firstOrCreate(['nama' => $m['nama']], $m + ['aktif' => true]);
        }

        $kategori = [
            [$resto->id, 'Dine-in'],
            [$resto->id, 'Take away'],
            [$resto->id, 'Online'],
            [$kopi->id, 'Kopi'],
            [$kopi->id, 'Non-kopi'],
            [$barber->id, 'Jasa Cukur'],
            [$barber->id, 'Pomade'],
            [$barber->id, 'Parfum'],
            [null, 'Pendapatan Lain-lain'],
        ];

        foreach ($kategori as [$unitId, $nama]) {
            KategoriPemasukan::query()->firstOrCreate(
                ['unit_usaha_id' => $unitId, 'nama' => $nama],
                ['aktif' => true],
            );
        }

        $jenis = [
            'Bahan Baku',
            'Gaji dan Upah',
            'Listrik/Air/Internet',
            'Sewa',
            'Peralatan',
            'Pemasaran',
            'Royalti/Fee Franchise',
            'Perawatan dan Perbaikan',
            'Transport',
            'Lain-lain',
        ];

        foreach ($jenis as $nama) {
            JenisPengeluaran::query()->firstOrCreate(['nama' => $nama], ['aktif' => true]);
        }

        $satuan = [
            ['nama' => 'Pieces', 'singkatan' => 'pcs'],
            ['nama' => 'Kilogram', 'singkatan' => 'kg'],
            ['nama' => 'Gram', 'singkatan' => 'gram'],
            ['nama' => 'Liter', 'singkatan' => 'liter'],
            ['nama' => 'Mililiter', 'singkatan' => 'ml'],
            ['nama' => 'Pack', 'singkatan' => 'pack'],
            ['nama' => 'Box', 'singkatan' => 'box'],
            ['nama' => 'Botol', 'singkatan' => 'botol'],
            ['nama' => 'Porsi', 'singkatan' => 'porsi'],
            ['nama' => 'Jam', 'singkatan' => 'jam'],
            ['nama' => 'Bulan', 'singkatan' => 'bulan'],
            ['nama' => 'Unit', 'singkatan' => 'unit'],
        ];

        foreach ($satuan as $s) {
            Satuan::query()->firstOrCreate(['nama' => $s['nama']], $s);
        }

        $satuanId = Satuan::query()->pluck('id', 'singkatan');
        $jenisId = JenisPengeluaran::query()->pluck('id', 'nama');
        $bahanBaku = $jenisId['Bahan Baku'] ?? null;

        // Item contoh supaya detail item pada pengeluaran langsung bisa dipakai.
        $items = [
            ['nama' => 'Beras', 'satuan' => 'kg', 'jenis' => $bahanBaku, 'harga' => 14000],
            ['nama' => 'Ayam Potong', 'satuan' => 'kg', 'jenis' => $bahanBaku, 'harga' => 38000],
            ['nama' => 'Minyak Goreng', 'satuan' => 'liter', 'jenis' => $bahanBaku, 'harga' => 17000],
            ['nama' => 'Bumbu Dapur', 'satuan' => 'pack', 'jenis' => $bahanBaku, 'harga' => 12000],
            ['nama' => 'Gas LPG 12 kg', 'satuan' => 'unit', 'jenis' => $bahanBaku, 'harga' => 210000],
            ['nama' => 'Biji Kopi Arabika', 'satuan' => 'kg', 'jenis' => $bahanBaku, 'harga' => 165000],
            ['nama' => 'Susu UHT', 'satuan' => 'liter', 'jenis' => $bahanBaku, 'harga' => 21000],
            ['nama' => 'Gula Aren Cair', 'satuan' => 'botol', 'jenis' => $bahanBaku, 'harga' => 28000],
            ['nama' => 'Cup Plastik 16 oz', 'satuan' => 'pack', 'jenis' => $bahanBaku, 'harga' => 32000],
            ['nama' => 'Pomade', 'satuan' => 'pcs', 'jenis' => $bahanBaku, 'harga' => 65000],
            ['nama' => 'Cukur Poles', 'satuan' => 'pcs', 'jenis' => $bahanBaku, 'harga' => 45000],
        ];

        foreach ($items as $item) {
            Item::query()->firstOrCreate(
                ['nama' => $item['nama']],
                [
                    'satuan_default_id' => $satuanId[$item['satuan']] ?? null,
                    'jenis_pengeluaran_id' => $item['jenis'],
                    'harga_terakhir' => $item['harga'],
                    'aktif' => true,
                ],
            );
        }
    }
    private function seedOwner(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@forsera.co.id'],
            [
                'name' => 'Owner Forsera Group',
                'password' => Hash::make(self::PASSWORD_DEMO),
                'aktif' => true,
                'must_change_password' => true,
            ],
        );

        $owner->syncRoles([User::ROLE_OWNER]);
    }

    /**
     * Akun contoh untuk mencoba alur per peran di lingkungan lokal.
     * Password sama dengan akun owner agar mudah diingat saat demo.
     */
    private function seedAkunDemo(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@forsera.co.id'],
            [
                'name' => 'Admin Keuangan',
                'password' => Hash::make(self::PASSWORD_DEMO),
                'aktif' => true,
                'must_change_password' => false,
            ],
        );
        $admin->syncRoles([User::ROLE_ADMIN]);

        $staf = User::query()->firstOrCreate(
            ['email' => 'staf@forsera.co.id'],
            [
                'name' => 'Staf Resto',
                'password' => Hash::make(self::PASSWORD_DEMO),
                'aktif' => true,
                'must_change_password' => false,
                'last_unit_usaha_id' => 1,
            ],
        );
        $staf->syncRoles([User::ROLE_STAF]);

        $resto = UnitUsaha::query()->where('kode', 'FRS')->first();
        if ($resto !== null && ! $staf->unitUsaha()->whereKey($resto->id)->exists()) {
            $staf->unitUsaha()->attach($resto->id);
        }
    }
}
