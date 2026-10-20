<?php

use App\Enums\ArahSelisih;
use App\Enums\StatusInsiden;
use App\Enums\StatusRekonsiliasi;
use App\Enums\StatusVerifikasi;
use App\Enums\TipePenyelesaian;
use App\Models\Insiden;
use App\Models\InsidenLog;
use App\Models\Pemasukan;
use App\Models\User;
use App\Services\InsidenService;
use App\Services\RekonsiliasiService;
use Illuminate\Validation\ValidationException;

/**
 * Membuat rekonsiliasi berselisih -100.000 beserta insidennya.
 *
 * @return array{insiden: Insiden, pemasukan: Pemasukan, owner: User}
 */
function insidenSelisih(array $master): array
{
    $owner = pengguna(User::ROLE_OWNER);
    $pembuat = pengguna(User::ROLE_ADMIN);

    $pemasukan = Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 1_000_000,
        'status_verifikasi' => StatusVerifikasi::BELUM_DICEK,
        'dibuat_oleh' => $pembuat->id,
    ]);

    $hasil = app(RekonsiliasiService::class)->simpan([
        'unit_usaha_id' => $master['resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'periode_mulai' => now()->startOfMonth()->toDateString(),
        'periode_selesai' => now()->toDateString(),
        'tanggal_diterima' => now()->toDateString(),
        'pemasukan_ids' => [$pemasukan->id],
        'total_diterima' => 900_000,
        'biaya_admin' => 0,
    ], $owner);

    return ['insiden' => $hasil['insiden'], 'pemasukan' => $pemasukan, 'owner' => $owner];
}

it('mencatat penyelesaian bertahap lalu menutup insiden', function () {
    $master = masterDasar();
    ['insiden' => $insiden, 'pemasukan' => $pemasukan, 'owner' => $owner] = insidenSelisih($master);

    $service = app(InsidenService::class);

    $service->selesaikan($insiden, [
        'tipe_penyelesaian' => TipePenyelesaian::SETORAN_SUSULAN->value,
        'nominal' => 40_000,
        'penyebab' => 'Kasir kurang menyetor',
        'catatan_penyelesaian' => 'Setoran susulan pertama',
    ], $owner);

    $insiden->refresh();

    expect($insiden->status)->toBe(StatusInsiden::OPEN)
        ->and($insiden->nominal_terselesaikan)->toBe(40_000)
        ->and($insiden->rekonsiliasi->status)->toBe(StatusRekonsiliasi::SELISIH);

    $service->selesaikan($insiden, [
        'tipe_penyelesaian' => TipePenyelesaian::SETORAN_SUSULAN->value,
        'nominal' => 60_000,
        'penyebab' => 'Kasir kurang menyetor',
        'catatan_penyelesaian' => 'Pelunasan setoran susulan',
    ], $owner);

    $insiden->refresh();

    expect($insiden->status)->toBe(StatusInsiden::SELESAI)
        ->and($insiden->nominal_terselesaikan)->toBe(100_000)
        ->and($insiden->rekonsiliasi->status)->toBe(StatusRekonsiliasi::SELESAI)
        ->and($pemasukan->refresh()->status_verifikasi)->toBe(StatusVerifikasi::SELESAI);

    expect(InsidenLog::query()->where('insiden_id', $insiden->id)->count())->toBeGreaterThanOrEqual(4);
});

it('menolak write-off oleh admin keuangan', function () {
    $master = masterDasar();
    ['insiden' => $insiden, 'owner' => $owner] = insidenSelisih($master);

    $admin = pengguna(User::ROLE_ADMIN);

    expect(fn () => app(InsidenService::class)->selesaikan($insiden, [
        'tipe_penyelesaian' => TipePenyelesaian::WRITE_OFF->value,
        'penyebab' => 'Tidak akan ditagih',
        'catatan_penyelesaian' => 'Dihapuskan',
    ], $admin))->toThrow(ValidationException::class);
});

it('mengizinkan owner menghapuskan selisih lewat write-off', function () {
    $master = masterDasar();
    ['insiden' => $insiden, 'owner' => $owner] = insidenSelisih($master);

    app(InsidenService::class)->selesaikan($insiden, [
        'tipe_penyelesaian' => TipePenyelesaian::WRITE_OFF->value,
        'penyebab' => 'Tidak akan ditagih',
        'catatan_penyelesaian' => 'Dihapuskan oleh Owner',
    ], $owner);

    expect($insiden->refresh()->status)->toBe(StatusInsiden::DIHAPUSKAN);
});

it('menolak catat pendapatan lain untuk selisih kurang', function () {
    $master = masterDasar();
    ['insiden' => $insiden, 'owner' => $owner] = insidenSelisih($master);

    expect($insiden->arah)->toBe(ArahSelisih::KURANG);

    expect(fn () => app(InsidenService::class)->selesaikan($insiden, [
        'tipe_penyelesaian' => TipePenyelesaian::CATAT_PENDAPATAN_LAIN->value,
        'penyebab' => 'Salah input',
        'catatan_penyelesaian' => 'Catat pendapatan',
    ], $owner))->toThrow(ValidationException::class);
});

it('menampilkan detail insiden dan timeline untuk owner', function () {
    $master = masterDasar();
    ['insiden' => $insiden, 'owner' => $owner] = insidenSelisih($master);

    $this->actingAs($owner)
        ->get(route('insiden.show', $insiden))
        ->assertOk()
        ->assertSee($insiden->kode)
        ->assertSee('Timeline')
        ->assertSee('Insiden dibuat otomatis');
});
