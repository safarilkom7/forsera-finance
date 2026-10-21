<?php

namespace App\Http\Controllers;

use App\Models\Insiden;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Rekonsiliasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __invoke(Request $request, string $jenis, int $id, int $index): StreamedResponse
    {
        $map = [
            'pemasukan' => Pemasukan::class,
            'pengeluaran' => Pengeluaran::class,
            'rekonsiliasi' => Rekonsiliasi::class,
            'insiden' => Insiden::class,
        ];

        abort_unless(isset($map[$jenis]), 404);

        $model = $map[$jenis]::query()->findOrFail($id);

        Gate::authorize('view', $model);

        $lampiran = collect($model->lampiran ?? [])->values()->get($index);

        abort_unless(is_array($lampiran) && isset($lampiran['path']), 404);

        $path = $lampiran['path'];

        abort_unless(Storage::disk('local')->exists($path), 404);

        $nama = $lampiran['nama_asli'] ?? basename($path);
        $mime = $lampiran['mime'] ?? 'application/octet-stream';

        // mode=inline dipakai untuk pratinjau gambar di daftar lampiran.
        $inline = $request->boolean('inline') || in_array($request->query('mode'), ['inline', 'lihat', 'preview'], true);

        activity('lampiran')
            ->performedOn($model)
            ->causedBy($request->user())
            ->withProperties(['path' => $path, 'ip' => $request->ip(), 'mode' => $inline ? 'inline' : 'unduh'])
            ->log($inline ? 'Melihat lampiran' : 'Mengunduh lampiran');

        $headers = [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ];

        if ($inline) {
            return Storage::disk('local')->response($path, $nama, $headers);
        }

        return Storage::disk('local')->download($path, $nama, $headers);
    }
}
