<?php

use App\Enums\StatusVerifikasi;
use App\Models\Pemasukan;
use App\Models\PeriodeTutup;
use App\Models\Rekonsiliasi;
use App\Models\User;
use App\Services\PengaturanService;

it('menyimpan pemasukan baru dengan status belum dicek', function () {
    $master = masterDasar();

    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pemasukan', [
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => '1.250.000',
        'note' => 'Omzet QRIS hari ini',
    ])->assertRedirect(route('pemasukan.index'));

    $pemasukan = Pemasukan::query()->latest('id')->first();

    expect($pemasukan)->not->toBeNull()
        ->and($pemasukan->nominal)->toBe(1_250_000)
        ->and($pemasukan->status_verifikasi)->toBe(StatusVerifikasi::BELUM_DICEK)
        ->and($pemasukan->dibuat_oleh)->toBe($staf->id);
});

it('menolak nominal di atas batas maksimum transaksi', function () {
    $master = masterDasar();
    app(PengaturanService::class)->set('nominal_maks_transaksi', 1_000_000);

    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pemasukan', [
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 1_000_001,
    ])->assertSessionHasErrors('nominal');

    expect(Pemasukan::query()->count())->toBe(0);
});

it('menolak input pemasukan untuk unit yang tidak ditugaskan', function () {
    $master = masterDasar();

    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pemasukan', [
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['kopi']->id,
        'kategori_pemasukan_id' => $master['kategori_kopi']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 50_000,
    ])->assertSessionHasErrors('unit_usaha_id');

    expect(Pemasukan::query()->count())->toBe(0);
});

it('menolak transaksi pada periode yang sudah ditutup', function () {
    $master = masterDasar();

    $owner = pengguna(User::ROLE_OWNER);

    PeriodeTutup::query()->create([
        'tahun' => now()->year,
        'bulan' => now()->month,
        'unit_usaha_id' => null,
        'ditutup_oleh' => $owner->id,
        'ditutup_at' => now(),
    ]);

    $this->actingAs($owner)->post('/pemasukan', [
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 100_000,
    ])->assertSessionHasErrors('tanggal');

    expect(Pemasukan::query()->count())->toBe(0);
});

it('mengunci pemasukan yang sudah direkonsiliasi', function () {
    $master = masterDasar();

    $owner = pengguna(User::ROLE_OWNER);

    $pemasukan = Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 500_000,
        'status_verifikasi' => StatusVerifikasi::SESUAI,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->put(route('pemasukan.update', $pemasukan), [
            'tanggal' => now()->toDateString(),
            'unit_usaha_id' => $master['resto']->id,
            'kategori_pemasukan_id' => $master['kategori_resto']->id,
            'metode_pembayaran_id' => $master['qris']->id,
            'nominal' => 600_000,
            'alasan' => 'Koreksi omzet',
        ])
        ->assertRedirect(route('pemasukan.index'));

    expect($pemasukan->refresh()->nominal)->toBe(600_000);
});

it('menampilkan pemeriksa transaksi dan tautan verifikasi sesuai peran', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 300_000,
        'status_verifikasi' => StatusVerifikasi::BELUM_DICEK,
        'dibuat_oleh' => $staf->id,
    ]);

    $rekonsiliasi = Rekonsiliasi::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'diverifikasi_oleh' => $owner->id,
    ]);

    Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 700_000,
        'status_verifikasi' => StatusVerifikasi::SESUAI,
        'rekonsiliasi_id' => $rekonsiliasi->id,
        'dibuat_oleh' => $staf->id,
    ]);

    $this->actingAs($owner)->get('/pemasukan')
        ->assertOk()
        ->assertSee('Belum Dicek')
        ->assertSee('Verifikasi')
        ->assertSee(route('rekonsiliasi.create'), false)
        ->assertSee($rekonsiliasi->kode)
        ->assertSee('dicek '.$owner->name);

    // Staf boleh input, tetapi tidak punya akses ke rekonsiliasi/verifikasi.
    $this->actingAs($staf)->get('/pemasukan')
        ->assertOk()
        ->assertDontSee(route('rekonsiliasi.create'), false);
});
