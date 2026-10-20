<?php

use App\Models\User;
use App\Services\BrandingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    app(BrandingService::class)->flushCache();
});

afterEach(function () {
    app(BrandingService::class)->flushCache();
});

it('memakai identitas bawaan sebelum diubah', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/dashboard')
        ->assertOk()
        ->assertSee('Forsera Group')
        ->assertSee('Keuangan &amp; Verifikasi', false);

    $branding = app(BrandingService::class)->semua();

    expect($branding['nama'])->toBe('Forsera Finance')
        ->and($branding['brand'])->toBe('Forsera Group')
        ->and($branding['inisial'])->toBe('FG')
        ->and($branding['logo_url'])->toBeNull();
});

it('mengubah nama, brand, dan title aplikasi', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->put(route('pengaturan.branding'), [
        'app_nama' => 'Kas Arobi',
        'app_brand' => 'Arobi Koffee',
        'app_title' => 'Laporan Harian',
    ])->assertRedirect(route('pengaturan.index'));

    $branding = app(BrandingService::class)->semua();

    expect($branding['nama'])->toBe('Kas Arobi')
        ->and($branding['brand'])->toBe('Arobi Koffee')
        ->and($branding['title'])->toBe('Laporan Harian')
        ->and($branding['inisial'])->toBe('AK');

    $this->actingAs($owner)->get('/dashboard')
        ->assertOk()
        ->assertSee('Arobi Koffee')
        ->assertSee('Laporan Harian')
        ->assertSee('— Kas Arobi</title>', false);

    auth()->logout();

    $this->get('/login')
        ->assertOk()
        ->assertSee('Arobi Koffee')
        ->assertSee('Laporan Harian');
});

it('mengunggah logo lalu menyajikannya lewat route publik', function () {
    Storage::fake('local');
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->put(route('pengaturan.branding'), [
        'app_nama' => 'Forsera Finance',
        'app_brand' => 'Forsera Group',
        'app_title' => 'Keuangan & Verifikasi',
        'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
    ])->assertRedirect(route('pengaturan.index'));

    $branding = app(BrandingService::class);

    expect($branding->logoPath())->not->toBeNull();
    Storage::disk('local')->assertExists($branding->logoPath());

    $this->get(route('branding.logo'))
        ->assertOk()
        ->assertHeader('content-type', 'image/png');

    $this->actingAs($owner)->get('/dashboard')
        ->assertOk()
        ->assertSee(route('branding.logo'), false);
});

it('menghapus logo dan kembali memakai inisial', function () {
    Storage::fake('local');
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $branding = app(BrandingService::class);
    $path = $branding->simpanLogo(UploadedFile::fake()->image('logo.png', 128, 128));

    $this->actingAs($owner)->put(route('pengaturan.branding'), [
        'app_nama' => 'Forsera Finance',
        'app_brand' => 'Forsera Group',
        'app_title' => 'Keuangan & Verifikasi',
        'hapus_logo' => 1,
    ])->assertRedirect(route('pengaturan.index'));

    expect(app(BrandingService::class)->logoPath())->toBeNull();
    Storage::disk('local')->assertMissing($path);

    $this->get(route('branding.logo'))->assertNotFound();
});

it('menolak berkas logo yang bukan gambar', function () {
    Storage::fake('local');
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->put(route('pengaturan.branding'), [
        'app_nama' => 'Forsera Finance',
        'app_brand' => 'Forsera Group',
        'app_title' => 'Keuangan & Verifikasi',
        'logo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('logo');

    expect(app(BrandingService::class)->logoPath())->toBeNull();
});

it('hanya owner yang dapat mengubah identitas aplikasi', function () {
    masterDasar();
    $admin = pengguna(User::ROLE_ADMIN);

    $this->actingAs($admin)->put(route('pengaturan.branding'), [
        'app_nama' => 'Kas Arobi',
        'app_brand' => 'Arobi Koffee',
        'app_title' => 'Laporan Harian',
    ])->assertForbidden();
});
