<?php

use App\Models\Pengeluaran;
use App\Models\Rekonsiliasi;
use App\Models\User;

it('menampilkan label status sebagai badge, bukan tombol', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    Pengeluaran::factory()->menunggu()->create([
        'unit_usaha_id' => $master['resto']->id,
        'total_nominal' => 150_000,
        'dibuat_oleh' => $staf->id,
    ]);

    $this->actingAs($owner)->get('/pengeluaran')
        ->assertOk()
        ->assertSee('class="badge badge-yellow"', false)
        ->assertSee('>Menunggu Persetujuan</span>', false);
});

it('membedakan warna tombol setujui, tolak, dan hapus', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    Pengeluaran::factory()->menunggu()->create([
        'unit_usaha_id' => $master['resto']->id,
        'total_nominal' => 250_000,
        'dibuat_oleh' => $staf->id,
    ]);

    $this->actingAs($owner)->get('/pengeluaran')
        ->assertOk()
        ->assertSee('btn-ghost-success btn-sm', false)
        ->assertSee('btn-ghost-warning btn-sm', false)
        ->assertSee('btn-ghost-danger btn-sm', false);
});

it('memisahkan status rekonsiliasi dari tombol batalkan', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $rekonsiliasi = Rekonsiliasi::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'diverifikasi_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)->get(route('rekonsiliasi.show', $rekonsiliasi))
        ->assertOk()
        ->assertSee('>Status</span>', false)
        ->assertSee('btn-danger-outline btn-sm', false);
});
