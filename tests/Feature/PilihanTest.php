<?php

use App\Models\User;
use Illuminate\Support\Facades\Blade;

it('memuat kolom pencarian dan tombol bersihkan pada dropdown', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/pemasukan/create')
        ->assertOk()
        // Kontrol native tetap ada sebagai sumber data dan untuk validasi required.
        ->assertSee('data-pilihan-asli', false)
        ->assertSee('data-pilihan-cari', false)
        ->assertSee('data-pilihan-bersihkan', false)
        ->assertSee('aria-label="Bersihkan pencarian"', false)
        ->assertSee('data-pilihan-status', false);
});

it('memaksa pencarian hanya pada dropdown yang ditandai searchable', function () {
    $biasa = Blade::render('<x-pilihan name="unit"><option value="1">Satu</option></x-pilihan>');
    $selaluCari = Blade::render('<x-pilihan name="unit" searchable><option value="1">Satu</option></x-pilihan>');

    expect($biasa)->not->toContain('data-pilihan-cari-paksa')
        ->and($selaluCari)->toContain('data-pilihan-cari-paksa');
});

it('menjaga opsi kategori tetap membawa unit usahanya', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/pemasukan/create')
        ->assertOk()
        ->assertSee('data-unit="'.$master['kategori_resto']->unit_usaha_id.'"', false)
        ->assertSee($master['kategori_resto']->nama);
});
