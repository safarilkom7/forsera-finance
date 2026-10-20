<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LampiranService
{
    public const MAX_FILES = 5;

    public const MAX_BYTES = 5 * 1024 * 1024;

    /** @var array<string, string> */
    public const MIME_DIIZINKAN = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    ];

    /**
     * @param  array<int, UploadedFile|string>  $files
     * @return array<int, array{path: string, nama_asli: string, mime: string, ukuran: int, diunggah_oleh: int|null, diunggah_at: string}>
     */
    public function simpan(array $files, string $folder, User $user, array $namaAsli = []): array
    {
        $files = array_values(array_filter($files));
        $this->pastikanJumlah($files);

        $hasil = [];

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $this->validasiFile($file);

                $nama = $file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension());
                $path = $file->storeAs(
                    $folder,
                    Str::uuid()->toString().'.'.$ext,
                    ['disk' => 'local', 'visibility' => 'private'],
                );

                $hasil[] = $this->meta($path, $nama, (int) $user->id);
            } elseif (is_string($file) && $file !== '') {
                $nama = $namaAsli[$index] ?? basename($file);
                $hasil[] = $this->meta($file, $nama, (int) $user->id);
            }
        }

        return $hasil;
    }

    /**
     * Bangun metadata dari path yang sudah disimpan (mis. unggahan form).
     *
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $namaAsli
     * @return array<int, array{path: string, nama_asli: string, mime: string, ukuran: int, diunggah_oleh: int|null, diunggah_at: string}>
     */
    public function dariPaths(array $paths, array $namaAsli, User $user): array
    {
        $paths = array_values(array_filter($paths, fn ($p) => is_string($p) && $p !== ''));
        $this->pastikanJumlah($paths);

        $hasil = [];

        foreach ($paths as $index => $path) {
            if (! Storage::disk('local')->exists($path)) {
                throw ValidationException::withMessages(['lampiran' => 'Berkas lampiran tidak ditemukan. Silakan unggah ulang.']);
            }

            $hasil[] = $this->meta($path, $namaAsli[$index] ?? basename($path), (int) $user->id);
        }

        return $hasil;
    }

    public function validasiFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['lampiran' => 'Ukuran setiap lampiran maksimal 5 MB.']);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();

        if (! isset(self::MIME_DIIZINKAN[$ext]) || self::MIME_DIIZINKAN[$ext] !== $mime) {
            throw ValidationException::withMessages([
                'lampiran' => 'Lampiran harus berupa JPG, PNG, WEBP, atau PDF yang valid.',
            ]);
        }
    }

    public function hapus(?array $metadata): void
    {
        foreach ($metadata ?? [] as $item) {
            $path = is_array($item) ? ($item['path'] ?? null) : $item;

            if (is_string($path) && $path !== '') {
                Storage::disk('local')->delete($path);
            }
        }
    }

    public function hapusYangTidakDipakai(array $lama, array $baru): void
    {
        $pathBaru = collect($baru)->pluck('path')->all();

        foreach ($lama as $item) {
            if (! in_array($item['path'] ?? null, $pathBaru, true)) {
                $this->hapus([$item]);
            }
        }
    }

    public function namaFile(array $metadata): string
    {
        return $metadata['nama_asli'] ?? basename($metadata['path'] ?? 'lampiran');
    }

    /**
     * @param  array<int, mixed>  $files
     */
    private function pastikanJumlah(array $files): void
    {
        if (count($files) > self::MAX_FILES) {
            throw ValidationException::withMessages(['lampiran' => 'Maksimal 5 lampiran per transaksi.']);
        }
    }

    /**
     * @return array{path: string, nama_asli: string, mime: string, ukuran: int, diunggah_oleh: int|null, diunggah_at: string}
     */
    private function meta(string $path, string $namaAsli, ?int $userId): array
    {
        $disk = Storage::disk('local');

        return [
            'path' => $path,
            'nama_asli' => $namaAsli,
            'mime' => $disk->mimeType($path) ?: 'application/octet-stream',
            'ukuran' => (int) $disk->size($path),
            'diunggah_oleh' => $userId,
            'diunggah_at' => now()->toIso8601String(),
        ];
    }
}
