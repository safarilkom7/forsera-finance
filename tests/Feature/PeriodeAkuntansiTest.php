<?php

use App\Models\Pemasukan;
use App\Models\PeriodeTutup;
use App\Models\User;
use App\Services\PengaturanService;
use App\Services\PeriodeAkuntansiService;
use App\Services\PeriodeFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

beforeEach(function () {
    app(PengaturanService::class)->flushCache();
});

afterEach(function () {
    app(PengaturanService::class)->flushCache();
});

it('membagi periode akuntansi dari tanggal 5 sampai tanggal 4 bulan berikutnya', function () {
    masterDasar();

    $service = app(PeriodeAkuntansiService::class);

    $awalPeriode = $service->untukTanggal('2026-10-05');
    $akhirPeriode = $service->untukTanggal('2026-11-04');
    $sebelumPeriode = $service->untukTanggal('2026-10-04');

    expect($service->tanggalMulai())->toBe(5)
        ->and($awalPeriode['label'])->toBe('Oktober 2026')
        ->and($awalPeriode['mulai']->toDateString())->toBe('2026-10-05')
        ->and($awalPeriode['selesai']->toDateString())->toBe('2026-11-04')
        ->and($awalPeriode['rentang'])->toBe('5 Okt 2026 – 4 Nov 2026')
        ->and($akhirPeriode['label'])->toBe('Oktober 2026')
        ->and($sebelumPeriode['label'])->toBe('September 2026')
        ->and($sebelumPeriode['selesai']->toDateString())->toBe('2026-10-04');
});

it('mengikuti tanggal mulai periode yang diubah di pengaturan', function () {
    masterDasar();
    app(PengaturanService::class)->set('tanggal_mulai_periode', 25);

    $service = app(PeriodeAkuntansiService::class);

    $periode = $service->untukTanggal('2026-10-25');
    $sebelum = $service->untukTanggal('2026-10-24');

    expect($service->tanggalMulai())->toBe(25)
        ->and($periode['mulai']->toDateString())->toBe('2026-10-25')
        ->and($periode['selesai']->toDateString())->toBe('2026-11-24')
        ->and($sebelum['label'])->toBe('September 2026');
});

it('menolak tanggal mulai periode di luar rentang 1 sampai 28', function () {
    masterDasar();

    $owner = pengguna(User::ROLE_OWNER);

    $payload = [];
    foreach (PengaturanService::DEFAULTS as $key => $meta) {
        $payload[$key] = $meta['value'];
    }

    $this->actingAs($owner)
        ->put(route('pengaturan.update'), array_merge($payload, ['tanggal_mulai_periode' => 31]))
        ->assertSessionHasErrors('tanggal_mulai_periode');

    expect(app(PengaturanService::class)->getInt('tanggal_mulai_periode'))->toBe(5);
});

it('menolak transaksi pada periode akuntansi yang sudah ditutup', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));

    $this->actingAs($owner)
        ->post(route('periode.store'), ['periode' => '2026-10'])
        ->assertRedirect(route('periode.index'));

    expect(PeriodeTutup::query()->aktif()->count())->toBe(1);

    // 3 Nov masih termasuk periode 5 Okt – 4 Nov 2026.
    $this->actingAs($owner)->post('/pemasukan', [
        'tanggal' => '2026-11-03',
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 100_000,
    ])->assertSessionHasErrors('tanggal');

    // 5 Nov sudah masuk periode berikutnya.
    $this->actingAs($owner)->post('/pemasukan', [
        'tanggal' => '2026-11-05',
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 100_000,
    ])->assertRedirect(route('pemasukan.index'));

    expect(Pemasukan::query()->count())->toBe(1);
});

it('memakai periode akuntansi untuk filter periode berjalan di dashboard', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));

    $request = Request::create('/dashboard', 'GET', ['periode' => 'periode_ini']);
    $request->setUserResolver(fn () => $owner);

    $hasil = app(PeriodeFilterService::class)->resolve($request, $owner);

    expect($hasil['mulai']->toDateString())->toBe('2026-10-05')
        ->and($hasil['selesai']->toDateString())->toBe('2026-11-04')
        ->and($hasil['label'])->toBe('Periode 5 Okt 2026 – 4 Nov 2026')
        ->and($hasil['tanggal_mulai'])->toBe(5);

    $this->actingAs($owner)->get('/dashboard')
        ->assertOk()
        ->assertSee('Periode 5 Okt 2026 – 4 Nov 2026')
        ->assertSee('Periode berjalan');
});

it('menampilkan rentang tanggal pada halaman tutup periode', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));

    $this->actingAs($owner)->get('/periode')
        ->assertOk()
        ->assertSee('Oktober 2026 · 5 Okt 2026 – 4 Nov 2026');
});
