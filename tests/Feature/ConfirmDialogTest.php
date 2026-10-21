<?php

use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Rekonsiliasi;
use App\Models\User;

it('memuat dialog konfirmasi global pada layout aplikasi', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 125_000,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)->get('/pemasukan')
        ->assertOk()
        ->assertSee('id="confirm-dialog"', false)
        ->assertSee('data-confirm-title="Hapus pemasukan?"', false)
        ->assertSee('data-confirm-severity="danger"', false)
        ->assertSee('akan dihapus permanen dan tercatat di audit log', false);
});

it('memakai dialog konfirmasi untuk menyetujui pengeluaran', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    Pengeluaran::factory()->menunggu()->create([
        'unit_usaha_id' => $master['resto']->id,
        'total_nominal' => 275_000,
        'dibuat_oleh' => $staf->id,
    ]);

    $this->actingAs($owner)->get('/pengeluaran')
        ->assertOk()
        ->assertSee('data-confirm-title="Setujui pengeluaran?"', false)
        ->assertSee('data-confirm-severity="info"', false)
        ->assertSee('data-confirm-label="Ya, setujui"', false)
        ->assertSee('akan disetujui dan ikut dihitung sebagai biaya', false);
});

it('memakai dialog konfirmasi untuk verifikasi pemasukan', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 340_000,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)->get('/pemasukan')
        ->assertOk()
        ->assertSee('data-confirm-title="Verifikasi pemasukan?"', false)
        ->assertSee('data-confirm-label="Ya, verifikasi"', false)
        ->assertSee('akan dicocokkan dengan dana yang benar-benar diterima', false);
});

it('memakai dialog konfirmasi saat keluar aplikasi', function () {
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/dashboard')
        ->assertOk()
        ->assertSee('data-confirm-title="Keluar dari aplikasi?"', false)
        ->assertSee('data-confirm-label="Ya, keluar"', false);
});

it('memuat dialog konfirmasi pada layout tamu', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('id="confirm-dialog"', false);
});

it('memakai dialog konfirmasi untuk menutup periode', function () {
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/periode')
        ->assertOk()
        ->assertSee('data-confirm-form', false)
        ->assertSee('data-confirm-title="Tutup periode?"', false)
        ->assertSee('data-confirm-label="Ya, tutup periode"', false);
});

it('menampilkan dialog alasan pada pembatalan rekonsiliasi', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $rekonsiliasi = Rekonsiliasi::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'diverifikasi_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)->get(route('rekonsiliasi.show', $rekonsiliasi))
        ->assertOk()
        ->assertSee('id="dialog-batal"', false)
        ->assertSee('Batalkan rekonsiliasi?');
});
