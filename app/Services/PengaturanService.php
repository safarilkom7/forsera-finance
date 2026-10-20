<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PengaturanService
{
    /** @var array<string, array{value: mixed, tipe: string, deskripsi: string}> */
    public const DEFAULTS = [
        'tanggal_mulai_periode' => [
            'value' => 5,
            'tipe' => 'integer',
            'min' => 1,
            'max' => 28,
            'deskripsi' => 'Tanggal awal periode akuntansi. Periode berjalan dari tanggal ini sampai sehari sebelum tanggal yang sama di bulan berikutnya (mulai 5 = 5 Okt s/d 4 Nov), sehingga gajian tanggal 6 jatuh setelah periode ditutup.',
        ],
        'toleransi_selisih' => [
            'value' => 1000,
            'tipe' => 'integer',
            'deskripsi' => 'Selisih rekonsiliasi dianggap sesuai bila nilainya tidak melebihi angka ini (Rupiah).',
        ],
        'batas_hari_verifikasi' => [
            'value' => 2,
            'tipe' => 'integer',
            'deskripsi' => 'Pemasukan yang belum dicek lebih lama dari jumlah hari ini masuk panel Perlu Perhatian.',
        ],
        'batas_hari_settlement' => [
            'value' => 3,
            'tipe' => 'integer',
            'deskripsi' => 'Dana yang menunggu settlement lebih lama dari jumlah hari ini masuk panel Perlu Perhatian.',
        ],
        'mdr_qris_persen' => [
            'value' => 0.7,
            'tipe' => 'decimal',
            'deskripsi' => 'Saran awal biaya admin QRIS dalam persen. Hanya saran, bisa diubah saat rekonsiliasi.',
        ],
        'batas_persetujuan_pengeluaran' => [
            'value' => 0,
            'tipe' => 'integer',
            'deskripsi' => 'Pengeluaran staf di atas nominal ini menunggu persetujuan Owner. 0 = nonaktif.',
        ],
        'batas_lampiran_pengeluaran' => [
            'value' => 50000,
            'tipe' => 'integer',
            'deskripsi' => 'Pengeluaran minimal yang sebaiknya punya lampiran; hanya memunculkan peringatan.',
        ],
        'lonjakan_harga_persen' => [
            'value' => 20,
            'tipe' => 'integer',
            'deskripsi' => 'Batas kenaikan harga item dibanding rata-rata 5 pembelian terakhir sebelum dianggap lonjakan.',
        ],
        'nominal_maks_transaksi' => [
            'value' => 2000000000,
            'tipe' => 'integer',
            'deskripsi' => 'Nominal maksimum satu transaksi pemasukan atau pengeluaran (Rupiah).',
        ],
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $meta = self::DEFAULTS[$key] ?? null;

        return Cache::rememberForever('pengaturan.'.$key, function () use ($key, $default, $meta) {
            $row = Pengaturan::query()->find($key);

            if ($row === null) {
                return $meta['value'] ?? $default;
            }

            return $row->nilai();
        });
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getDecimal(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, $default);
    }

    public function set(string $key, mixed $value, ?string $deskripsi = null): Pengaturan
    {
        $meta = self::DEFAULTS[$key] ?? null;
        $tipe = $meta['tipe'] ?? 'string';

        $record = Pengaturan::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'tipe' => $tipe,
                'deskripsi' => $deskripsi ?? $meta['deskripsi'] ?? null,
            ],
        );

        Cache::forget('pengaturan.'.$key);

        activity('pengaturan')
            ->causedBy(auth()->user())
            ->withProperties([
                'key' => $key,
                'nilai_baru' => $value,
                'ip' => request()->ip(),
            ])
            ->log('Mengubah pengaturan');

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    public function semua(): array
    {
        $hasil = [];

        foreach (array_keys(self::DEFAULTS) as $key) {
            $hasil[$key] = $this->get($key);
        }

        return $hasil;
    }

    /**
     * Isi pengaturan default yang belum ada (dipakai seeder dan installer).
     */
    public function pastikanDefault(): void
    {
        DB::transaction(function () {
            foreach (self::DEFAULTS as $key => $meta) {
                Pengaturan::query()->firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => (string) $meta['value'],
                        'tipe' => $meta['tipe'],
                        'deskripsi' => $meta['deskripsi'],
                    ],
                );
            }
        });
    }

    public function flushCache(): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            Cache::forget('pengaturan.'.$key);
        }
    }
}
