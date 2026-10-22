<?php

use App\Models\User;

it('menyaring pengguna lewat filter nama dan email', function () {
    $owner = pengguna(User::ROLE_OWNER);
    User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@contoh.test', 'aktif' => true]);
    User::factory()->create(['name' => 'Citra Lestari', 'email' => 'citra@contoh.test', 'aktif' => true]);

    $this->actingAs($owner)->get('/pengguna?f_nama=budi')
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertDontSee('Citra Lestari');

    $this->actingAs($owner)->get('/pengguna?f_email=citra@contoh')
        ->assertOk()
        ->assertSee('Citra Lestari')
        ->assertDontSee('Budi Santoso');
});

it('menyaring pengguna berdasarkan peran', function () {
    $owner = pengguna(User::ROLE_OWNER);
    $admin = pengguna(User::ROLE_ADMIN);
    $staf = pengguna(User::ROLE_STAF, [masterDasar()['resto']]);

    $this->actingAs($owner)->get('/pengguna?f_peran=admin_keuangan')
        ->assertOk()
        ->assertSee($admin->name)
        ->assertDontSee($staf->name);

    // Nilai peran di luar daftar diabaikan, bukan menghasilkan halaman kosong.
    $this->actingAs($owner)->get('/pengguna?f_peran=superadmin')
        ->assertOk()
        ->assertSee($staf->name)
        ->assertDontSee('Reset filter');
});

it('menyaring pengguna berdasarkan unit usaha termasuk tanpa unit', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $stafResto = pengguna(User::ROLE_STAF, [$master['resto']]);
    $stafKopi = pengguna(User::ROLE_STAF, [$master['kopi']]);

    $this->actingAs($owner)->get('/pengguna?f_unit='.$master['resto']->id)
        ->assertOk()
        ->assertSee($stafResto->name)
        ->assertDontSee($stafKopi->name);

    $this->actingAs($owner)->get('/pengguna?f_unit=tanpa')
        ->assertOk()
        ->assertSee($owner->name)
        ->assertDontSee($stafResto->name)
        ->assertDontSee($stafKopi->name);
});

it('menyaring pengguna berdasarkan status aktif dan riwayat login', function () {
    $owner = pengguna(User::ROLE_OWNER);
    $nonaktif = pengguna(User::ROLE_STAF, [masterDasar()['kopi']]);
    $pernahLogin = pengguna(User::ROLE_STAF, [masterDasar()['resto']]);

    $nonaktif->update(['aktif' => false]);
    $pernahLogin->update(['last_login_at' => now()]);

    $this->actingAs($owner)->get('/pengguna?f_status=nonaktif')
        ->assertOk()
        ->assertSee($nonaktif->name)
        ->assertDontSee($pernahLogin->name);

    $this->actingAs($owner)->get('/pengguna?f_login=ada')
        ->assertOk()
        ->assertSee($pernahLogin->name)
        ->assertDontSee($nonaktif->name);

    $this->actingAs($owner)->get('/pengguna?f_login=kosong')
        ->assertOk()
        ->assertSee($nonaktif->name)
        ->assertDontSee($pernahLogin->name);
});

it('menampilkan baris filter kolom pada tabel pengguna', function () {
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/pengguna')
        ->assertOk()
        ->assertSee('data-filter-form', false)
        ->assertSee('Filter nama', false)
        ->assertSee('Filter email', false)
        ->assertSee('Filter peran', false)
        ->assertSee('Filter unit', false)
        ->assertSee('Filter status', false)
        ->assertSee('Filter login', false);
});
