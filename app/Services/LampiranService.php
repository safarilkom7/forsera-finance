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

    public const MAX_KB = 10 * 1024;

    public const MAX_BYTES = self::MAX_KB * 1024;

    /**
     * Ekstensi yang diterima. Foto dari iPhone (HEIC) dan dokumen kantor
     * (Word/Excel) ikut didukung karena itu yang paling sering diunggah staf.
     *
     * @var array<int, string>
     */
    public const EKSTENSI_DIIZINKAN = [
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
    ];

    /**
     * Mime yang sah untuk setiap ekstensi. Beberapa peramban/perangkat
     * mengirim varian mime yang berbeda untuk format yang sama.
     *
     * @var array<string, array<int, string>>
     */
    public const MIME_DIIZINKAN = [
        'jpg' => ['image/jpeg', 'image/jpg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/jpg', 'image/pjpeg'],
        'png' => ['image/png', 'image/x-png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'heic' => ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'],
        'heif' => ['image/heif', 'image/heic', 'image/heif-sequence', 'image/heic-sequence'],
        'pdf' => ['application/pdf', 'application/x-pdf', 'application/acrobat', 'applications/vnd.pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-word', 'application/x-msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    ];

    /**
     * Aturan validasi siap pakai untuk FormRequest.
     *
     * Tipe file diperiksa ulang di validasiFile() agar pesan galatnya
     * menyebut nama berkas dan daftar format yang didukung.
     *
     * @return array<int, string>
     */
    public static function aturan(): array
    {
        return ['file', 'max:'.self::MAX_KB];
    }

    /**
     * @return array<string, string>
     */
    public static function pesan(string $field = 'lampiran'): array
    {
        return [
            $field.'.array' => 'Format unggahan tidak dikenali. Muat ulang halaman lalu coba lagi.',
            $field.'.max' => 'Maksimal '.self::MAX_FILES.' berkas per transaksi.',
            $field.'.*.file' => 'Berkas gagal diunggah. Periksa ukuran berkas lalu coba lagi.',
            $field.'.*.uploaded' => 'Berkas gagal diunggah (melebihi batas server). Perkecil berkas lalu coba lagi.',
            $field.'.*.max' => 'Ukuran setiap berkas maksimal '.self::labelUkuran().'.',
            $field.'.*' => 'Berkas tidak dapat diunggah. Gunakan format '.self::labelTipe().'.',
        ];
    }

    public static function labelTipe(): string
    {
        return 'JPG, PNG, WEBP, HEIC, GIF, PDF, DOC, DOCX, XLS, atau XLSX';
    }

    public static function labelUkuran(): string
    {
        return (int) (self::MAX_KB / 1024).' MB';
    }

    /**
     * Nilai atribut accept untuk input file.
     */
    public static function accept(): string
    {
        $ekstensi = array_map(fn ($ext) => '.'.$ext, self::EKSTENSI_DIIZINKAN);

        return implode(',', array_merge($ekstensi, ['image/*', '.pdf']));
    }

    /**
     * Buang entri kosong yang tetap dikirim peramban (mis. input file tanpa berkas).
     *
     * @param  array<int, mixed>  $files
     * @return array<int, UploadedFile|string>
     */
    public static function bersihkan(array $files): array
    {
        return array_values(array_filter($files, function ($file) {
            if ($file instanceof UploadedFile) {
                return $file->getError() !== UPLOAD_ERR_NO_FILE;
            }

            return is_string($file) && $file !== '';
        }));
    }

    /**
     * @param  array<int, UploadedFile|string>  $files
     * @return array<int, array{path: string, nama_asli: string, mime: string, ukuran: int, diunggah_oleh: int|null, diunggah_at: string}>
     */
    public function simpan(array $files, string $folder, User $user, array $namaAsli = []): array
    {
        $files = self::bersihkan($files);
        $this->pastikanJumlah($files);

        $hasil = [];

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $this->validasiFile($file);

                $nama = $file->getClientOriginalName();
                $ext = $this->ekstensiAman($file);
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
     * Bangun metadata dari path yang sudah tersimpan (mis. unggahan form).
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
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'lampiran' => $this->pesanGalatUnggah($file),
            ]);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'lampiran' => 'Ukuran berkas '.$file->getClientOriginalName().' melebihi '.self::labelUkuran().'.',
            ]);
        }

        $ekstensi = strtolower($file->getClientOriginalExtension());

        if ($ekstensi === '') {
            $ekstensi = $this->ekstensiDariMime((string) $file->getMimeType());
        }

        if (! in_array($ekstensi, self::EKSTENSI_DIIZINKAN, true)) {
            throw ValidationException::withMessages([
                'lampiran' => 'Format '.$file->getClientOriginalName().' belum didukung. Gunakan '.self::labelTipe().'.',
            ]);
        }

        $mime = (string) $file->getMimeType();

        if (! $this->mimeSah($ekstensi, $mime)) {
            throw ValidationException::withMessages([
                'lampiran' => 'Isi berkas '.$file->getClientOriginalName().' tidak sesuai dengan ekstensinya. Gunakan berkas asli dari sumbernya.',
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
            throw ValidationException::withMessages([
                'lampiran' => 'Maksimal '.self::MAX_FILES.' lampiran per transaksi.',
            ]);
        }
    }

    private function mimeSah(string $ekstensi, string $mime): bool
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));

        if (in_array($mime, self::MIME_DIIZINKAN[$ekstensi] ?? [], true)) {
            return true;
        }

        // Sebagian berkas kantor lama dikenali sebagai octet-stream oleh PHP.
        return $mime === 'application/octet-stream'
            && in_array($ekstensi, ['doc', 'docx', 'xls', 'xlsx'], true);
    }

    private function ekstensiAman(UploadedFile $file): string
    {
        $ekstensi = strtolower($file->getClientOriginalExtension());

        if ($ekstensi !== '' && in_array($ekstensi, self::EKSTENSI_DIIZINKAN, true)) {
            return $ekstensi;
        }

        return $this->ekstensiDariMime((string) $file->getMimeType()) ?: 'bin';
    }

    private function ekstensiDariMime(string $mime): string
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));

        foreach (self::MIME_DIIZINKAN as $ekstensi => $daftar) {
            if (in_array($mime, $daftar, true)) {
                return $ekstensi;
            }
        }

        return '';
    }

    private function pesanGalatUnggah(UploadedFile $file): string
    {
        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran berkas melebihi batas server. Perkecil berkas lalu coba lagi.',
            UPLOAD_ERR_PARTIAL => 'Berkas hanya terunggah sebagian. Ulangi unggah.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada berkas yang dipilih.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Server gagal menyimpan berkas. Hubungi Admin Keuangan.',
            default => 'Berkas gagal diunggah. Coba lagi dengan berkas lain.',
        };
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
