<?php

use App\Enums\StatusVerifikasi;
use App\Models\Item;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Satuan;
use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\MasterDataService;

it('menghapus master data yang belum dipakai', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $satuan = Satuan::query()->create(['nama' => 'Dus Kecil', 'singkatan' => 'dus']);

    $this->actingAs($owner)
        ->delete(route('master.hapus', ['jenis' => 'satuan', 'id' => $satuan->id]))
        ->assertRedirect(route('master.satuan.index'))
        ->assertSessionHas('sukses');

    expect(Satuan::query()->find($satuan->id))->toBeNull();
});

it('menolak menghapus master data yang masih dipakai', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    // Kategori pemasukan hasil seeder menunjuk unit ini.
    $this->actingAs($owner)
        ->delete(route('master.hapus', ['jenis' => 'unit', 'id' => $master['resto']->id]))
        ->assertRedirect(route('master.unit.index'))
        ->assertSessionHas('gagal');

    expect($master['resto']->fresh())->not->toBeNull();
});

it('menghapus beberapa master data sekaligus lewat ceklist', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $satuan = Satuan::query()->create(['nama' => 'Renceng', 'singkatan' => 'rcg']);

    $itemA = Item::query()->create(['nama' => 'Tissue Meja', 'satuan_default_id' => $satuan->id, 'aktif' => true]);
    $itemB = Item::query()->create(['nama' => 'Sabun Cuci', 'satuan_default_id' => $satuan->id, 'aktif' => true]);

    $this->actingAs($owner)
        ->post(route('master.hapus-massal', 'item'), ['id' => [$itemA->id, $itemB->id]])
        ->assertRedirect(route('master.item.index'))
        ->assertSessionHas('sukses');

    expect(Item::query()->whereIn('id', [$itemA->id, $itemB->id])->count())->toBe(0);
});

it('menyembunyikan dan menolak hapus master data untuk admin keuangan', function () {
    $master = masterDasar();
    $admin = pengguna(User::ROLE_ADMIN);
    $satuan = Satuan::query()->create(['nama' => 'Lusin', 'singkatan' => 'lsn']);

    $this->actingAs($admin)->get(route('master.satuan.index'))
        ->assertOk()
        ->assertDontSee('data-pilih-semua', false);

    $this->actingAs($admin)
        ->delete(route('master.hapus', ['jenis' => 'satuan', 'id' => $satuan->id]))
        ->assertForbidden();

    expect($satuan->fresh())->not->toBeNull();
});

it('owner menghapus pemasukan lewat ceklist', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $a = Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 120_000,
        'dibuat_oleh' => $owner->id,
    ]);

    $b = Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 80_000,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->post(route('pemasukan.hapus-massal'), ['id' => [$a->id, $b->id]])
        ->assertRedirect()
        ->assertSessionHas('sukses');

    expect(Pemasukan::withTrashed()->whereIn('id', [$a->id, $b->id])->whereNotNull('deleted_at')->count())->toBe(2);
});

it('melewati pemasukan yang sudah diverifikasi saat hapus massal', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $bebas = Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 90_000,
        'dibuat_oleh' => $owner->id,
    ]);

    $terkunci = Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 70_000,
        'status_verifikasi' => StatusVerifikasi::SESUAI,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->post(route('pemasukan.hapus-massal'), ['id' => [$bebas->id, $terkunci->id]])
        ->assertSessionHas('sukses', fn (string $pesan) => str_contains($pesan, '1 pemasukan dihapus') && str_contains($pesan, 'dilewati'));

    expect($bebas->fresh()?->trashed())->toBeTrue()
        ->and($terkunci->fresh()?->trashed())->toBeFalse();
});

it('hanya owner yang boleh menghapus pemasukan dan pengeluaran', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $pemasukan = Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 65_000,
        'dibuat_oleh' => $staf->id,
    ]);

    $pengeluaran = Pengeluaran::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'total_nominal' => 45_000,
        'dibuat_oleh' => $staf->id,
    ]);

    $this->actingAs($staf)
        ->delete(route('pemasukan.destroy', $pemasukan))
        ->assertForbidden();

    $this->actingAs($staf)
        ->post(route('pemasukan.hapus-massal'), ['id' => [$pemasukan->id]])
        ->assertForbidden();

    $this->actingAs($staf)
        ->post(route('pengeluaran.hapus-massal'), ['id' => [$pengeluaran->id]])
        ->assertForbidden();

    expect($pemasukan->fresh()?->trashed())->toBeFalse()
        ->and($pengeluaran->fresh()?->trashed())->toBeFalse();

    $this->actingAs($owner)
        ->post(route('pengeluaran.hapus-massal'), ['id' => [$pengeluaran->id]])
        ->assertSessionHas('sukses');

    expect($pengeluaran->fresh()?->trashed())->toBeTrue();
});

it('bisa menghapus master yang hanya dipakai transaksi yang sudah dihapus', function () {
    $owner = pengguna(User::ROLE_OWNER);

    $pengeluaran = Pengeluaran::factory()->create(['dibuat_oleh' => $owner->id]);
    $unit = $pengeluaran->unitUsaha;

    $pengeluaran->delete();

    expect(app(MasterDataService::class)->keteranganPemakaian('unit', [$unit->id])[$unit->id] ?? '')
        ->toBe('');

    $this->actingAs($owner)
        ->delete(route('master.hapus', ['jenis' => 'unit', 'id' => $unit->id]))
        ->assertRedirect(route('master.unit.index'))
        ->assertSessionHas('sukses');

    expect(UnitUsaha::query()->find($unit->id))->toBeNull()
        ->and(Pengeluaran::withTrashed()->find($pengeluaran->id))->toBeNull();
});

it('bisa menghapus satuan yang hanya dipakai detail transaksi yang sudah dihapus', function () {
    $owner = pengguna(User::ROLE_OWNER);

    $satuanItem = Satuan::query()->create(['nama' => 'Pcs', 'singkatan' => 'pcs']);
    $satuanBeli = Satuan::query()->create(['nama' => 'Rim', 'singkatan' => 'rim']);

    $item = Item::query()->create(['nama' => 'Tissue', 'satuan_default_id' => $satuanItem->id, 'aktif' => true]);

    $pengeluaran = Pengeluaran::factory()->create();
    $pengeluaran->detail()->create([
        'item_id' => $item->id,
        'jumlah' => 2,
        'satuan_id' => $satuanBeli->id,
        'harga_satuan' => 5_000,
        'subtotal' => 10_000,
    ]);

    $pengeluaran->delete();

    $this->actingAs($owner)
        ->delete(route('master.hapus', ['jenis' => 'satuan', 'id' => $satuanBeli->id]))
        ->assertRedirect(route('master.satuan.index'))
        ->assertSessionHas('sukses');

    expect(Satuan::query()->find($satuanBeli->id))->toBeNull()
        ->and(Pengeluaran::withTrashed()->find($pengeluaran->id))->toBeNull();
});

it('menampilkan ceklist hapus pada halaman pemasukan untuk owner saja', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $admin = pengguna(User::ROLE_ADMIN);

    Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 55_000,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)->get(route('pemasukan.index'))
        ->assertOk()
        ->assertSee('data-pilih-semua', false)
        ->assertSee('data-pilih-bar', false);

    $this->actingAs($admin)->get(route('pemasukan.index'))
        ->assertOk()
        ->assertDontSee('data-pilih-semua', false);
});
