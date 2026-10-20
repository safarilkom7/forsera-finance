<?php

use App\Enums\StatusPersetujuanPengeluaran;
use App\Models\Item;
use App\Models\JenisPengeluaran;
use App\Models\Pengeluaran;
use App\Models\Satuan;
use App\Models\User;
use App\Services\PengaturanService;

function siapkanItem(): Item
{
    $satuan = Satuan::factory()->create(['nama' => 'Kilogram Uji', 'singkatan' => 'kgu']);

    return Item::factory()->create([
        'nama' => 'Beras Uji',
        'satuan_default_id' => $satuan->id,
        'jenis_pengeluaran_id' => JenisPengeluaran::query()->firstOrFail()->id,
        'harga_terakhir' => 14_000,
    ]);
}

it('menghitung total dari detail item di server', function () {
    $master = masterDasar();
    $item = siapkanItem();

    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pengeluaran', [
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'jenis_pengeluaran_id' => $item->jenis_pengeluaran_id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'total_nominal' => 1_000,
        'detail' => [
            ['item_id' => $item->id, 'jumlah' => 2, 'satuan_id' => $item->satuan_default_id, 'harga_satuan' => '14.000'],
            ['item_id' => $item->id, 'jumlah' => 3, 'satuan_id' => $item->satuan_default_id, 'harga_satuan' => '2.000'],
        ],
    ])->assertRedirect(route('pengeluaran.index'));

    $pengeluaran = Pengeluaran::query()->with('detail')->latest('id')->first();

    expect($pengeluaran->total_nominal)->toBe(34_000)
        ->and($pengeluaran->detail)->toHaveCount(2)
        ->and($pengeluaran->detail->sum('subtotal'))->toBe(34_000)
        ->and($pengeluaran->dari_kas_harian)->toBeTrue();
});

it('menolak pengeluaran tanpa detail dan tanpa total', function () {
    $master = masterDasar();
    $item = siapkanItem();

    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pengeluaran', [
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'jenis_pengeluaran_id' => $item->jenis_pengeluaran_id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'total_nominal' => 0,
        'detail' => [],
    ])->assertSessionHasErrors('total_nominal');

    expect(Pengeluaran::query()->count())->toBe(0);
});

it('menahan pengeluaran staf di atas batas untuk persetujuan Owner', function () {
    $master = masterDasar();

    app(PengaturanService::class)->set('batas_persetujuan_pengeluaran', 100_000);

    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);
    $owner = pengguna(User::ROLE_OWNER);
    $admin = pengguna(User::ROLE_ADMIN);

    $jenis = JenisPengeluaran::query()->firstOrFail();

    $this->actingAs($staf)->post('/pengeluaran', [
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'jenis_pengeluaran_id' => $jenis->id,
        'metode_pembayaran_id' => $master['transfer']->id,
        'total_nominal' => 150_000,
    ])->assertRedirect(route('pengeluaran.index'));

    $pengeluaran = Pengeluaran::query()->latest('id')->firstOrFail();

    expect($pengeluaran->status_persetujuan)->toBe(StatusPersetujuanPengeluaran::MENUNGGU);

    $this->actingAs($admin)
        ->post(route('pengeluaran.setujui', $pengeluaran))
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('pengeluaran.setujui', $pengeluaran))
        ->assertRedirect(route('pengeluaran.index'));

    $pengeluaran->refresh();

    expect($pengeluaran->status_persetujuan)->toBe(StatusPersetujuanPengeluaran::DISETUJUI)
        ->and($pengeluaran->disetujui_oleh)->toBe($owner->id);
});

it('owner dapat menolak pengeluaran yang menunggu', function () {
    $master = masterDasar();

    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);
    $owner = pengguna(User::ROLE_OWNER);

    $pengeluaran = Pengeluaran::factory()->menunggu()->create([
        'unit_usaha_id' => $master['resto']->id,
        'dibuat_oleh' => $staf->id,
    ]);

    $this->actingAs($owner)
        ->post(route('pengeluaran.tolak', $pengeluaran))
        ->assertRedirect(route('pengeluaran.index'));

    expect($pengeluaran->refresh()->status_persetujuan)->toBe(StatusPersetujuanPengeluaran::DITOLAK);
});
