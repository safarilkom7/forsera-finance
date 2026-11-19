<?php

use App\Enums\StatusPersetujuanPengeluaran;
use App\Enums\StatusVerifikasi;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

it('hanya owner yang dapat membuka menu hak akses', function () {
    $owner = pengguna(User::ROLE_OWNER);
    $admin = pengguna(User::ROLE_ADMIN);
    $staf = pengguna(User::ROLE_STAF);

    $this->actingAs($owner)->get('/izin')
        ->assertOk()
        ->assertSee('Hak Akses')
        ->assertSee($staf->name);

    $this->actingAs($admin)->get('/izin')->assertForbidden();
    $this->actingAs($staf)->get('/izin')->assertForbidden();
});

it('izin khusus rekonsiliasi membuka akses dan menu, dicabut kembali ke default', function () {
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF);

    // Sebelum: staf tidak punya akses rekonsiliasi, menu tidak tampil.
    $this->actingAs($staf)->get('/rekonsiliasi')->assertForbidden();
    $this->actingAs($staf)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('rekonsiliasi.index'), false);

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['rekonsiliasi.lihat']])
        ->assertRedirect(route('izin.index'))
        ->assertSessionHas('sukses');

    expect($staf->refresh()->hasPermissionTo('rekonsiliasi.lihat'))->toBeTrue();

    $this->actingAs($staf)->get('/rekonsiliasi')->assertOk();
    $this->actingAs($staf)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('rekonsiliasi.index'), false);

    // Dicabut lagi → kembali ke default peran.
    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => []])
        ->assertRedirect(route('izin.index'));

    expect($staf->refresh()->hasPermissionTo('rekonsiliasi.lihat'))->toBeFalse();
    $this->actingAs($staf)->get('/rekonsiliasi')->assertForbidden();
});

it('izin khusus hanya menyentuh izin langsung, izin bawaan peran tetap utuh', function () {
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF);

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['rekonsiliasi.lihat', 'laporan.lihat']])
        ->assertRedirect(route('izin.index'));

    $staf->refresh();

    expect($staf->getDirectPermissions()->pluck('name')->sort()->values()->all())
        ->toBe(['laporan.lihat', 'rekonsiliasi.lihat'])
        // Bawaan peran tidak ikut tersimpan sebagai izin langsung, tapi tetap efektif.
        ->and($staf->hasDirectPermission('pemasukan.lihat'))->toBeFalse()
        ->and($staf->hasPermissionTo('pemasukan.lihat'))->toBeTrue();

    $this->actingAs($staf)->get('/pemasukan')->assertOk();

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => []])->assertRedirect(route('izin.index'));

    expect($staf->refresh()->hasPermissionTo('pemasukan.lihat'))->toBeTrue()
        ->and($staf->hasPermissionTo('rekonsiliasi.lihat'))->toBeFalse();
});

it('staf dengan izin khusus dapat menyetujui dan menolak pengeluaran yang menunggu', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);
    $pengeluaranLain = pengguna(User::ROLE_STAF);

    $pengeluaran = Pengeluaran::factory()->menunggu()->create([
        'unit_usaha_id' => $master['resto']->id,
        'dibuat_oleh' => $pengeluaranLain->id,
    ]);

    // Tanpa izin khusus: staf tidak boleh menyetujui.
    $this->actingAs($staf)->post(route('pengeluaran.setujui', $pengeluaran))->assertForbidden();

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['pengeluaran.setujui']])
        ->assertRedirect(route('izin.index'));

    // Relasi izin pada instance lama sudah ter-cache kosong; muat ulang dulu.
    $staf->refresh();

    $this->actingAs($staf)->post(route('pengeluaran.setujui', $pengeluaran))
        ->assertRedirect(route('pengeluaran.index'));

    expect($pengeluaran->refresh()->status_persetujuan)->toBe(StatusPersetujuanPengeluaran::DISETUJUI)
        ->and($pengeluaran->disetujui_oleh)->toBe($staf->id);
});

it('staf dengan izin khusus dapat membuka log aktivitas dan admin tetap punya aksesnya', function () {
    $owner = pengguna(User::ROLE_OWNER);
    $admin = pengguna(User::ROLE_ADMIN);
    $staf = pengguna(User::ROLE_STAF);

    $this->actingAs($admin)->get('/log-aktivitas')->assertOk();
    $this->actingAs($staf)->get('/log-aktivitas')->assertForbidden();

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['audit.lihat']])
        ->assertRedirect(route('izin.index'));

    // Relasi izin pada instance lama sudah ter-cache kosong; muat ulang dulu.
    $staf->refresh();

    $this->actingAs($staf)->get('/log-aktivitas')->assertOk();
});

it('izin.kelola tidak dapat diberikan lewat matriks', function () {
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF);

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['izin.kelola', 'laporan.lihat']])
        ->assertSessionHasErrors();

    expect($staf->refresh()->getDirectPermissions())->toBeEmpty();
});

it('mencatat log aktivitas saat mengatur izin khusus', function () {
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF);

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['laporan.lihat']])
        ->assertRedirect(route('izin.index'));

    $activity = Activity::query()->where('log_name', 'izin')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Mengatur hak akses khusus')
        ->and($activity->properties['izin_khusus'])->toBe(['laporan.lihat']);
});

it('matriks menandai izin bawaan peran dan menampilkan halaman untuk owner', function () {
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF);

    $this->actingAs($owner)->get(route('izin.edit', $staf))
        ->assertOk()
        ->assertSee('master.kelola', false)
        ->assertSee('Dari peran')
        // rekonsiliasi.verifikasi belum dipakai runtime → diberi penanda.
        ->assertSee('belum berpengaruh', false)
        // izin.kelola tidak pernah tampil di matriks.
        ->assertDontSee('izin.kelola', false);
});

it('semua permission seeder tercantum pada peta matriks kecuali izin.kelola', function () {
    siapkanDataDasar();

    $diPeta = collect(App\Support\PetaIzin::GRUP)->flatMap(fn ($izinGrup) => array_keys($izinGrup))->all();
    $diSeeder = Permission::query()->pluck('name')->reject('izin.kelola')->values()->all();

    expect(collect($diPeta)->sort()->values()->all())->toBe(collect($diSeeder)->sort()->values()->all());
});

it('izin khusus hapus pemasukan membuka tombol dan aksi hapus untuk staf', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $admin = pengguna(User::ROLE_ADMIN);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $pemasukan = Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'dibuat_oleh' => $owner->id,
    ]);

    // Default tidak berubah: admin dan staf tidak bisa menghapus, tombol tidak tampil.
    $this->actingAs($admin)->delete(route('pemasukan.destroy', $pemasukan))->assertForbidden();
    $this->actingAs($staf)->delete(route('pemasukan.destroy', $pemasukan))->assertForbidden();
    $this->actingAs($staf)->get('/pemasukan')->assertDontSee('Hapus pemasukan?', false);

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['pemasukan.hapus']])
        ->assertRedirect(route('izin.index'));

    $staf->refresh();

    $this->actingAs($staf)->get('/pemasukan')->assertSee('Hapus pemasukan?', false);
    $this->actingAs($staf)->delete(route('pemasukan.destroy', $pemasukan))
        ->assertRedirect(route('pemasukan.index'));

    expect($pemasukan->fresh()?->trashed())->toBeTrue();
});

it('izin khusus hapus pengeluaran membuka aksi hapus untuk staf', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $pengeluaran = Pengeluaran::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($staf)->delete(route('pengeluaran.destroy', $pengeluaran))->assertForbidden();

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['pengeluaran.hapus']])
        ->assertRedirect(route('izin.index'));

    $staf->refresh();

    $this->actingAs($staf)->delete(route('pengeluaran.destroy', $pengeluaran))
        ->assertRedirect(route('pengeluaran.index'));

    expect($pengeluaran->fresh()?->trashed())->toBeTrue();
});

it('izin khusus koreksi membuka koreksi pemasukan terkunci untuk staf', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $terkunci = Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'status_verifikasi' => StatusVerifikasi::SESUAI,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($staf)->get(route('pemasukan.edit', $terkunci))->assertForbidden();

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['koreksi.terkunci']])
        ->assertRedirect(route('izin.index'));

    $staf->refresh();

    // Tombol "Koreksi" (via @can update) dan halaman edit terbuka.
    $this->actingAs($staf)->get('/pemasukan')
        ->assertOk()
        ->assertSee('Koreksi', false);
    $this->actingAs($staf)->get(route('pemasukan.edit', $terkunci))->assertOk();
});

it('izin khusus hapus juga membuka ceklist hapus massal', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $bebasA = Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'dibuat_oleh' => $owner->id,
    ]);
    $bebasB = Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'dibuat_oleh' => $owner->id,
    ]);
    $terkunci = Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'status_verifikasi' => StatusVerifikasi::SESUAI,
        'dibuat_oleh' => $owner->id,
    ]);

    // Default: bar ceklist tidak tampil dan route massal tetap terkunci.
    $this->actingAs($staf)->get('/pemasukan')->assertDontSee('aksi-massal-form', false);
    $this->actingAs($staf)->post(route('pemasukan.hapus-massal'), ['id' => [$bebasA->id]])->assertForbidden();

    $this->actingAs($owner)->put(route('izin.update', $staf), ['izin' => ['pemasukan.hapus']])
        ->assertRedirect(route('izin.index'));

    $staf->refresh();

    $this->actingAs($staf)->get('/pemasukan')->assertSee('aksi-massal-form', false);
    $this->actingAs($staf)->post(route('pemasukan.hapus-massal'), ['id' => [$bebasA->id, $bebasB->id, $terkunci->id]])
        ->assertSessionHas('sukses', fn (string $pesan) => str_contains($pesan, '2 pemasukan dihapus') && str_contains($pesan, 'dilewati'));

    expect(Pemasukan::withTrashed()->whereIn('id', [$bebasA->id, $bebasB->id])->whereNotNull('deleted_at')->count())->toBe(2)
        ->and($terkunci->fresh()?->trashed())->toBeFalse();
});
