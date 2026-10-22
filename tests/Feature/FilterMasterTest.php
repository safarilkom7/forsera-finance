<?php

use App\Models\Item;
use App\Models\Satuan;
use App\Models\User;

it('menyaring unit usaha lewat filter kolom', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/master/unit?f_nama=barber')
        ->assertOk()
        ->assertSee('FORSERA BARBERSHOP')
        ->assertDontSee('FORSERA RESTO');

    $this->actingAs($owner)->get('/master/unit?f_tipe=RESTO')
        ->assertOk()
        ->assertSee('FORSERA RESTO')
        ->assertDontSee('FORSERA BARBERSHOP');

    $this->actingAs($owner)->get('/master/unit?f_status=nonaktif')
        ->assertOk()
        ->assertSee('Tidak ada unit usaha yang cocok');
});

it('menyaring metode pembayaran lewat filter kolom', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/master/metode?f_tipe=QRIS')
        ->assertOk()
        ->assertSee('QRIS')
        ->assertDontSee('>Transfer Bank</td>', false);

    $this->actingAs($owner)->get('/master/metode?f_nama=zzz')
        ->assertOk()
        ->assertSee('Tidak ada metode yang cocok');
});

it('menyaring kategori per unit termasuk kategori global', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/master/kategori?f_unit=tanpa')
        ->assertOk()
        ->assertSee('Pendapatan Lain-lain</td>', false)
        ->assertDontSee('Dine-in');

    // Kategori global tetap disebut pada keterangan halaman, jadi cek sel tabelnya.
    $this->actingAs($owner)->get('/master/kategori?f_unit='.$master['resto']->id)
        ->assertOk()
        ->assertSee('Dine-in')
        ->assertDontSee('Pendapatan Lain-lain</td>', false);
});

it('menyaring jenis pengeluaran dan satuan lewat filter kolom', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/master/jenis?f_nama=gaji')
        ->assertOk()
        ->assertSee('Gaji dan Upah')
        ->assertSee('Reset filter');

    $this->actingAs($owner)->get('/master/satuan?f_singkatan=kg')
        ->assertOk()
        ->assertSee('Kilogram')
        ->assertDontSee('>Pieces</td>', false);
});

it('menyaring item berdasarkan satuan, jenis, dan ketersediaan harga', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $beras = Item::query()->where('nama', 'Beras')->firstOrFail();
    $kilogram = Satuan::query()->where('singkatan', 'kg')->firstOrFail();

    Item::query()->create([
        'nama' => 'Item Tanpa Jenis',
        'satuan_default_id' => $kilogram->id,
        'jenis_pengeluaran_id' => null,
        'harga_terakhir' => null,
        'aktif' => true,
    ]);

    $this->actingAs($owner)->get('/master/item?f_satuan='.$beras->satuan_default_id)
        ->assertOk()
        ->assertSee('Beras')
        ->assertDontSee('Pomade');

    $this->actingAs($owner)->get('/master/item?f_jenis=tanpa')
        ->assertOk()
        ->assertSee('Item Tanpa Jenis')
        ->assertDontSee('Beras');

    $this->actingAs($owner)->get('/master/item?f_harga=kosong')
        ->assertOk()
        ->assertSee('Item Tanpa Jenis')
        ->assertDontSee('Beras');
});

it('menampilkan baris filter kolom pada semua tabel master', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    foreach (['unit', 'metode', 'kategori', 'jenis', 'satuan', 'item'] as $master) {
        $this->actingAs($owner)->get('/master/'.$master)
            ->assertOk()
            ->assertSee('data-filter-form', false)
            ->assertSee('data-filter-input', false)
            ->assertSee('data-filter-bersih', false);
    }
});

it('mengabaikan parameter filter yang tidak dikenal', function () {
    masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/master/unit?f_kolom_aneh=xyz&f_nama=resto')
        ->assertOk()
        ->assertSee('FORSERA RESTO')
        ->assertDontSee('FORSERA BARBERSHOP');

    // Nilai status di luar aktif/nonaktif diabaikan, bukan dianggap nonaktif.
    $this->actingAs($owner)->get('/master/unit?f_status=aneh')
        ->assertOk()
        ->assertSee('FORSERA RESTO')
        ->assertDontSee('Reset filter');
});
