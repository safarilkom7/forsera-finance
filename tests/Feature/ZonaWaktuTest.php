<?php

use App\Models\User;

it('menampilkan zona waktu dari konfigurasi di halaman login', function () {
    masterDasar();
    config(['app.timezone' => 'Asia/Makassar']);

    $this->get('/login')
        ->assertOk()
        ->assertSee('Zona waktu Asia/Makassar.')
        ->assertDontSee('Asia/Jakarta');
});

it('menampilkan zona waktu dari konfigurasi di dashboard dan profil', function () {
    masterDasar();

    $owner = pengguna(User::ROLE_OWNER);
    config(['app.timezone' => 'Asia/Jayapura']);

    $this->actingAs($owner)->get('/dashboard')
        ->assertOk()
        ->assertSee('Semua angka mengikuti zona waktu Asia/Jayapura.');

    $this->actingAs($owner)->get(route('profil.edit'))
        ->assertOk()
        ->assertSee('Asia/Jayapura')
        ->assertDontSee('Asia/Jakarta');
});

it('menerjemahkan zona waktu menjadi singkatan yang benar', function () {
    config(['app.timezone' => 'Asia/Jakarta']);
    expect(singkatan_zona_waktu())->toBe('WIB');

    config(['app.timezone' => 'Asia/Makassar']);
    expect(singkatan_zona_waktu())->toBe('WITA');

    config(['app.timezone' => 'Asia/Jayapura']);
    expect(singkatan_zona_waktu())->toBe('WIT');
});
