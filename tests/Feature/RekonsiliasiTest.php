<?php

use App\Enums\ArahSelisih;
use App\Enums\StatusInsiden;
use App\Enums\StatusRekonsiliasi;
use App\Enums\StatusVerifikasi;
use App\Models\Insiden;
use App\Models\Pemasukan;
use App\Models\Rekonsiliasi;
use App\Models\User;
use App\Services\RekonsiliasiService;
use Illuminate\Validation\ValidationException;

function buatPemasukanQris(array $master, int $nominal, User $pembuat): Pemasukan
{
    return Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => $nominal,
        'status_verifikasi' => StatusVerifikasi::BELUM_DICEK,
        'dibuat_oleh' => $pembuat->id,
    ]);
}

function dataRekonsiliasi(array $master, array $ids, array $tambahan = []): array
{
    return array_merge([
        'unit_usaha_id' => $master['resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'periode_mulai' => now()->startOfMonth()->toDateString(),
        'periode_selesai' => now()->toDateString(),
        'tanggal_diterima' => now()->toDateString(),
        'pemasukan_ids' => $ids,
        'total_diterima' => 0,
        'biaya_admin' => 0,
    ], $tambahan);
}

it('menyatakan sesuai bila selisih dalam toleransi', function () {
    $master = masterDasar();
    $admin = pengguna(User::ROLE_ADMIN);
    $pembuat = pengguna(User::ROLE_ADMIN);

    $pemasukan = buatPemasukanQris($master, 1_000_000, $pembuat);

    $hasil = app(RekonsiliasiService::class)->simpan(
        dataRekonsiliasi($master, [$pemasukan->id], ['total_diterima' => 1_000_500]),
        $admin,
    );

    expect($hasil['rekonsiliasi']->status)->toBe(StatusRekonsiliasi::SESUAI)
        ->and($hasil['rekonsiliasi']->selisih)->toBe(500)
        ->and($hasil['insiden'])->toBeNull()
        ->and($pemasukan->refresh()->status_verifikasi)->toBe(StatusVerifikasi::SESUAI);

    expect(Insiden::query()->count())->toBe(0);
});

it('membuat insiden otomatis saat selisih melebihi toleransi', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $pembuat = pengguna(User::ROLE_ADMIN);

    $pemasukan = buatPemasukanQris($master, 1_000_000, $pembuat);

    $hasil = app(RekonsiliasiService::class)->simpan(
        dataRekonsiliasi($master, [$pemasukan->id], ['total_diterima' => 900_000]),
        $owner,
    );

    $rekonsiliasi = $hasil['rekonsiliasi'];

    expect($rekonsiliasi->status)->toBe(StatusRekonsiliasi::SELISIH)
        ->and($rekonsiliasi->selisih)->toBe(-100_000)
        ->and($rekonsiliasi->kode)->toStartWith('REK-')
        ->and($hasil['insiden'])->not->toBeNull()
        ->and($hasil['insiden']->nominal_selisih)->toBe(100_000)
        ->and($hasil['insiden']->arah)->toBe(ArahSelisih::KURANG)
        ->and($hasil['insiden']->status)->toBe(StatusInsiden::OPEN)
        ->and($pemasukan->refresh()->rekonsiliasi_id)->toBe($rekonsiliasi->id)
        ->and($pemasukan->refresh()->status_verifikasi)->toBe(StatusVerifikasi::SELISIH);
});

it('menandai pemasukan menunggu settlement tanpa membuat rekonsiliasi', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $pembuat = pengguna(User::ROLE_ADMIN);

    $pemasukan = buatPemasukanQris($master, 750_000, $pembuat);

    $data = dataRekonsiliasi($master, [$pemasukan->id], ['menunggu_settlement' => true]);
    unset($data['tanggal_diterima']);

    $hasil = app(RekonsiliasiService::class)->simpan($data, $owner);

    expect($hasil['menunggu'])->toBeTrue()
        ->and($hasil['rekonsiliasi'])->toBeNull()
        ->and(Rekonsiliasi::query()->count())->toBe(0)
        ->and($pemasukan->refresh()->status_verifikasi)->toBe(StatusVerifikasi::MENUNGGU_SETTLEMENT);
});

it('menolak maker-checker saat admin memverifikasi pemasukan buatannya sendiri', function () {
    $master = masterDasar();
    $admin = pengguna(User::ROLE_ADMIN);

    $pemasukan = buatPemasukanQris($master, 500_000, $admin);

    expect(fn () => app(RekonsiliasiService::class)->simpan(
        dataRekonsiliasi($master, [$pemasukan->id], ['total_diterima' => 500_000]),
        $admin,
    ))->toThrow(ValidationException::class);

    expect(Rekonsiliasi::query()->count())->toBe(0);
});

it('staf tidak dapat membuka halaman rekonsiliasi', function () {
    $master = masterDasar();

    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->get('/rekonsiliasi')->assertForbidden();
    $this->actingAs($staf)->get('/insiden')->assertForbidden();
});
