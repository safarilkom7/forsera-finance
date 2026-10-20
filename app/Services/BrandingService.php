<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Identitas aplikasi (logo, nama, brand, title) yang bisa diubah Owner lewat Pengaturan.
 *
 * Disimpan di tabel `pengaturan` dengan key `app_*`, logo disimpan di disk privat `local`
 * dan disajikan lewat route publik `branding.logo` (tanpa perlu symlink storage).
 */
class BrandingService
{
    public const LOGO_DISK = 'local';

    public const LOGO_FOLDER = 'branding';

    public const LOGO_MAX_KB = 2048;

    public const DEFAULTS = [
        'app_nama' => [
            'value' => 'Forsera Finance',
            'deskripsi' => 'Nama aplikasi pada judul tab browser, halaman login, dan laporan PDF.',
        ],
        'app_brand' => [
            'value' => 'Forsera Group',
            'deskripsi' => 'Nama brand/perusahaan yang tampil di sidebar dan hak cipta.',
        ],
        'app_title' => [
            'value' => 'Keuangan & Verifikasi',
            'deskripsi' => 'Subjudul singkat di bawah nama brand. Boleh dikosongkan.',
        ],
        'app_logo' => [
            'value' => null,
            'deskripsi' => 'Logo aplikasi (PNG/JPG/WEBP, maksimal 2 MB).',
        ],
    ];

    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    public function nilai(string $key): ?string
    {
        $meta = self::DEFAULTS[$key] ?? null;

        if ($meta === null) {
            return null;
        }

        return Cache::rememberForever('branding.'.$key, function () use ($key, $meta) {
            $nilai = Pengaturan::query()->find($key)?->value;

            return ($nilai === null || $nilai === '') ? $meta['value'] : $nilai;
        });
    }

    /**
     * @return array{nama: string, brand: string, title: string, logo_path: string|null, logo_url: string|null, inisial: string}
     */
    public function semua(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $brand = $this->nilai('app_brand') ?: (string) self::DEFAULTS['app_brand']['value'];
        $logoPath = $this->logoPath();

        return $this->memo = [
            'nama' => $this->nilai('app_nama') ?: (string) self::DEFAULTS['app_nama']['value'],
            'brand' => $brand,
            'title' => (string) ($this->nilai('app_title') ?? ''),
            'logo_path' => $logoPath,
            'logo_url' => $logoPath === null
                ? null
                : route('branding.logo', ['v' => substr(md5($logoPath), 0, 8)]),
            'inisial' => $this->inisial($brand),
        ];
    }

    public function logoPath(): ?string
    {
        $path = $this->nilai('app_logo');

        if ($path === null) {
            return null;
        }

        return Storage::disk(self::LOGO_DISK)->exists($path) ? $path : null;
    }

    /**
     * @param  array{nama: string, brand: string, title?: string|null}  $data
     */
    public function simpanIdentitas(array $data): void
    {
        $this->set('app_nama', trim($data['nama']));
        $this->set('app_brand', trim($data['brand']));
        $this->set('app_title', trim((string) ($data['title'] ?? '')));
    }

    public function simpanLogo(UploadedFile $file): string
    {
        $lama = $this->nilai('app_logo');

        if ($lama !== null) {
            Storage::disk(self::LOGO_DISK)->delete($lama);
        }

        $ekstensi = strtolower($file->getClientOriginalExtension() ?: 'png');
        $path = $file->storeAs(
            self::LOGO_FOLDER,
            'logo-'.Str::random(10).'.'.$ekstensi,
            self::LOGO_DISK,
        );

        $this->set('app_logo', $path);

        return $path;
    }

    public function hapusLogo(): void
    {
        $lama = $this->nilai('app_logo');

        if ($lama !== null) {
            Storage::disk(self::LOGO_DISK)->delete($lama);
        }

        $this->set('app_logo', '');
    }

    public function pastikanDefault(): void
    {
        foreach (self::DEFAULTS as $key => $meta) {
            Pengaturan::query()->firstOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $meta['value'],
                    'tipe' => 'string',
                    'deskripsi' => $meta['deskripsi'],
                ],
            );
        }
    }

    public function flushCache(): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            Cache::forget('branding.'.$key);
        }

        $this->memo = null;
    }

    private function set(string $key, string $value): void
    {
        Pengaturan::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'tipe' => 'string',
                'deskripsi' => self::DEFAULTS[$key]['deskripsi'] ?? null,
            ],
        );

        Cache::forget('branding.'.$key);
        $this->memo = null;
    }

    private function inisial(string $brand): string
    {
        $kata = preg_split('/\s+/', trim($brand)) ?: [];
        $huruf = '';

        foreach ($kata as $satu) {
            if ($satu !== '') {
                $huruf .= mb_substr($satu, 0, 1);
            }
        }

        $huruf = mb_strtoupper($huruf !== '' ? $huruf : 'FG');

        return mb_substr($huruf, 0, 2);
    }
}
