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

        activity('lampiran')
            ->performedOn($model)
            ->causedBy($request->user())
            ->withProperties(['path' => $path, 'ip' => $request->ip()])
            ->log('Mengunduh lampiran');

        return Storage::disk('local')->download(
            $path,
            $lampiran['nama_asli'] ?? basename($path),
            ['Content-Type' => $lampiran['mime'] ?? 'application/octet-stream'],
        );
    }
}
