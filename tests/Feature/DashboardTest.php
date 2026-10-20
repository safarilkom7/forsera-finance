<?php

use App\Enums\StatusVerifikasi;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Support\Carbon;

it('menghitung kartu dashboard sesuai data terfilter', function () {
    $master = masterDasar();

    $pembuat = pengguna(User::ROLE_OWNER);

    Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 1_000_000,
        'status_verifikasi' => StatusVerifikasi::SESUAI,
        'dibuat_oleh' => $pembuat->id,
    ]);

    Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['kopi']->id,
        'kategori_pemasukan_id' => $master['kategori_kopi']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 500_000,
        'status_verifikasi' => StatusVerifikasi::BELUM_DICEK,
        'dibuat_oleh' => $pembuat->id,
    ]);

    Pengeluaran::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'total_nominal' => 250_000,
        'dibuat_oleh' => $pembuat->id,
    ]);

    $mulai = Carbon::now()->startOfMonth();
    $selesai = Carbon::now()->endOfMonth();
    $service = app(DashboardService::class);

    $semua = $service->ringkasan($mulai, $selesai, null);

    expect($semua['total_pemasukan'])->toBe(1_500_000)
        ->and($semua['total_pengeluaran'])->toBe(250_000)
        ->and($semua['laba'])->toBe(1_250_000)
        ->and($semua['terverifikasi_nominal'])->toBe(1_000_000)
        ->and($semua['terverifikasi_persen'])->toBe(66.67);

    $perUnit = $service->ringkasan($mulai, $selesai, $master['resto']->id);

    expect($perUnit['total_pemasukan'])->toBe(1_000_000)
        ->and($perUnit['total_pengeluaran'])->toBe(250_000);
});

it('menampilkan dashboard staf hanya untuk unitnya', function () {
    $master = masterDasar();

    $pembuat = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 123_000,
        'dibuat_oleh' => $staf->id,
    ]);

    Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['kopi']->id,
        'kategori_pemasukan_id' => $master['kategori_kopi']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 987_000,
        'dibuat_oleh' => $pembuat->id,
    ]);

    $this->actingAs($staf)->get('/dashboard')
        ->assertOk()
        ->assertSee('Rp 123.000')
        ->assertDontSee('Rp 987.000');
});

it('menampilkan halaman laporan dan mengekspor excel serta pdf', function () {
    $master = masterDasar();

    $owner = pengguna(User::ROLE_OWNER);

    Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 800_000,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)->get('/laporan')
        ->assertOk()
        ->assertSee('Laba rugi')
        ->assertSee('Buku kas');

    $this->actingAs($owner)->get('/laporan/ekspor-excel')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($owner)->get('/laporan/ekspor-pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('mengekspor daftar pemasukan ke excel', function () {
    masterDasar();

    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/pemasukan/ekspor')
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
