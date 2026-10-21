<?php

use App\Models\Pemasukan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * @return array<string, mixed>
 */
function dataPemasukan(array $master, array $tambahan = []): array
{
    return array_merge([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 250_000,
    ], $tambahan);
}

it('menyimpan lampiran pdf, docx, dan heic dari iPhone', function () {
    $master = masterDasar();
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pemasukan', dataPemasukan($master, [
        'lampiran' => [
            UploadedFile::fake()->create('struk.pdf', 40, 'application/pdf'),
            UploadedFile::fake()->create('rekap.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            UploadedFile::fake()->create('nota.HEIC', 60, 'image/heic'),
        ],
    ]))->assertRedirect(route('pemasukan.index'));

    $pemasukan = Pemasukan::query()->latest('id')->firstOrFail();

    expect($pemasukan->lampiran)->toHaveCount(3)
        ->and(array_column($pemasukan->lampiran, 'nama_asli'))->toBe(['struk.pdf', 'rekap.docx', 'nota.HEIC']);

    foreach ($pemasukan->lampiran as $file) {
        Storage::disk('local')->assertExists($file['path']);
    }

    expect($pemasukan->lampiran[1]['path'])->toEndWith('.docx')
        ->and($pemasukan->lampiran[2]['path'])->toEndWith('.heic');
});

it('menolak lampiran yang melebihi 10 MB', function () {
    $master = masterDasar();
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pemasukan', dataPemasukan($master, [
        'lampiran' => [UploadedFile::fake()->create('scan.pdf', 10_241, 'application/pdf')],
    ]))->assertSessionHasErrors('lampiran.0');

    expect(Pemasukan::query()->count())->toBe(0);
});

it('menolak format berkas yang belum didukung', function () {
    $master = masterDasar();
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pemasukan', dataPemasukan($master, [
        'lampiran' => [UploadedFile::fake()->create('aplikasi.exe', 10, 'application/x-msdownload')],
    ]))->assertSessionHasErrors('lampiran.0');

    expect(Pemasukan::query()->count())->toBe(0);
});

it('menolak berkas yang isinya tidak sesuai ekstensi', function () {
    $master = masterDasar();
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pemasukan', dataPemasukan($master, [
        'lampiran' => [UploadedFile::fake()->create('nota.pdf', 10, 'image/png')],
    ]))->assertSessionHasErrors('lampiran.0');

    expect(Pemasukan::query()->count())->toBe(0);
});

it('menolak lebih dari lima lampiran dalam satu transaksi', function () {
    $master = masterDasar();
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pemasukan', dataPemasukan($master, [
        'lampiran' => collect(range(1, 6))
            ->map(fn ($i) => UploadedFile::fake()->create("bukti-{$i}.pdf", 10, 'application/pdf'))
            ->all(),
    ]))->assertSessionHasErrors('lampiran');

    expect(Pemasukan::query()->count())->toBe(0);
});

it('menampilkan pratinjau inline hanya untuk pengguna yang berhak', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);
    $stafLain = pengguna(User::ROLE_STAF, [$master['kopi']]);

    Storage::disk('local')->put('lampiran/pemasukan/nota.png', 'isi-gambar');

    $pemasukan = Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 100_000,
        'dibuat_oleh' => $owner->id,
        'lampiran' => [[
            'path' => 'lampiran/pemasukan/nota.png',
            'nama_asli' => 'nota.png',
            'mime' => 'image/png',
            'ukuran' => 10,
            'diunggah_oleh' => $owner->id,
            'diunggah_at' => now()->toIso8601String(),
        ]],
    ]);

    $url = route('lampiran.download', ['jenis' => 'pemasukan', 'id' => $pemasukan->id, 'index' => 0]);

    $response = $this->actingAs($owner)->get($url.'?inline=1');

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('inline');

    $this->actingAs($owner)->get($url)->assertOk();

    $this->actingAs($stafLain)->get($url)->assertForbidden();
});

it('menghapus lampiran lama saat pemasukan diubah', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    Storage::disk('local')->put('lampiran/pemasukan/lama-1.pdf', 'lama');
    Storage::disk('local')->put('lampiran/pemasukan/lama-2.pdf', 'lama');

    $lampiran = collect(['lama-1.pdf', 'lama-2.pdf'])->map(fn ($nama) => [
        'path' => 'lampiran/pemasukan/'.$nama,
        'nama_asli' => $nama,
        'mime' => 'application/pdf',
        'ukuran' => 4,
        'diunggah_oleh' => $owner->id,
        'diunggah_at' => now()->toIso8601String(),
    ])->all();

    $pemasukan = Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 100_000,
        'dibuat_oleh' => $owner->id,
        'lampiran' => $lampiran,
    ]);

    $this->actingAs($owner)->put(route('pemasukan.update', $pemasukan), dataPemasukan($master, [
        'nominal' => 150_000,
        'hapus_lampiran' => ['lampiran/pemasukan/lama-1.pdf'],
        'lampiran' => [UploadedFile::fake()->create('baru.pdf', 10, 'application/pdf')],
    ]))->assertRedirect(route('pemasukan.index'));

    $pemasukan->refresh();

    expect($pemasukan->lampiran)->toHaveCount(2)
        ->and(array_column($pemasukan->lampiran, 'nama_asli'))->toBe(['lama-2.pdf', 'baru.pdf']);

    Storage::disk('local')->assertMissing('lampiran/pemasukan/lama-1.pdf');
    Storage::disk('local')->assertExists('lampiran/pemasukan/lama-2.pdf');
});

it('mengabaikan entri berkas kosong yang ikut terkirim peramban', function () {
    $master = masterDasar();
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $kosong = new UploadedFile(
        tempnam(sys_get_temp_dir(), 'kosong'),
        '',
        'application/octet-stream',
        UPLOAD_ERR_NO_FILE,
        true,
    );

    $this->actingAs($staf)->post('/pemasukan', dataPemasukan($master, [
        'lampiran' => [
            UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'),
            $kosong,
        ],
    ]))->assertRedirect(route('pemasukan.index'));

    expect(Pemasukan::query()->latest('id')->firstOrFail()->lampiran)->toHaveCount(1);
});

it('menampilkan pesan galat lampiran di form setelah unggahan ditolak', function () {
    $master = masterDasar();
    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);

    $this->actingAs($staf)->post('/pemasukan', dataPemasukan($master, [
        'lampiran' => [UploadedFile::fake()->create('aplikasi.exe', 10, 'application/x-msdownload')],
    ]));

    $html = $this->actingAs($staf)->get('/pemasukan/create')->assertOk()->getContent();

    expect($html)->toContain('Format aplikasi.exe belum didukung.')
        ->toContain('Gunakan JPG, PNG, WEBP, HEIC, GIF, PDF, DOC, DOCX, XLS, atau XLSX.');
});

it('memuat form transaksi dengan enctype multipart', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($owner)->get('/pemasukan/create')
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false);

    $this->actingAs($owner)->get('/pengeluaran/create')
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false);

    Pemasukan::factory()->create([
        'tanggal' => now()->toDateString(),
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 90_000,
        'dibuat_oleh' => $owner->id,
    ]);

    $this->actingAs($owner)->get('/rekonsiliasi/buat?unit='.$master['resto']->id.'&metode='.$master['qris']->id.'&mulai='.now()->subDays(10)->toDateString().'&selesai='.now()->toDateString())
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false);
});

it('menolak total lampiran melebihi batas saat mengubah pemasukan', function () {
    $master = masterDasar();
    $owner = pengguna(User::ROLE_OWNER);

    $lampiran = collect(range(1, 4))->map(fn ($i) => [
        'path' => "lampiran/pemasukan/lama-{$i}.pdf",
        'nama_asli' => "lama-{$i}.pdf",
        'mime' => 'application/pdf',
        'ukuran' => 4,
        'diunggah_oleh' => $owner->id,
        'diunggah_at' => now()->toIso8601String(),
    ])->all();

    foreach ($lampiran as $file) {
        Storage::disk('local')->put($file['path'], 'lama');
    }

    $pemasukan = Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['qris']->id,
        'nominal' => 100_000,
        'dibuat_oleh' => $owner->id,
        'lampiran' => $lampiran,
    ]);

    $this->actingAs($owner)->put(route('pemasukan.update', $pemasukan), dataPemasukan($master, [
        'nominal' => 100_000,
        'lampiran' => [
            UploadedFile::fake()->create('tambahan-1.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('tambahan-2.pdf', 10, 'application/pdf'),
        ],
    ]))->assertSessionHasErrors('lampiran');

    expect($pemasukan->refresh()->lampiran)->toHaveCount(4);
});
