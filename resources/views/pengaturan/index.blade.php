@extends('layouts.app')

@section('title', 'Pengaturan')
@section('subtitle', 'Identitas aplikasi dan parameter bisnis')

@section('content')
    <form method="POST" action="{{ route('pengaturan.branding') }}" enctype="multipart/form-data"
          class="card card-pad mb-5 max-w-4xl space-y-5">
        @csrf
        @method('PUT')

        <div>
            <h2 class="section-title">Identitas aplikasi</h2>
            <p class="hint mt-1">Tampil di sidebar, halaman login, judul tab browser, dan laporan PDF.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="app_nama" class="label">Nama aplikasi</label>
                <input id="app_nama" name="app_nama" value="{{ old('app_nama', $branding['nama']) }}" required maxlength="60"
                       class="input @error('app_nama') input-error @enderror">
                <p class="hint">Dipakai pada judul tab dan footer laporan.</p>
                @error('app_nama')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="app_brand" class="label">Brand / perusahaan</label>
                <input id="app_brand" name="app_brand" value="{{ old('app_brand', $branding['brand']) }}" required maxlength="60"
                       class="input @error('app_brand') input-error @enderror">
                <p class="hint">Nama yang tampil besar di sidebar dan hak cipta.</p>
                @error('app_brand')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="app_title" class="label">Title / subjudul</label>
                <input id="app_title" name="app_title" value="{{ old('app_title', $branding['title']) }}" maxlength="80"
                       class="input @error('app_title') input-error @enderror"
                       placeholder="Contoh: Keuangan &amp; Verifikasi">
                <p class="hint">Baris kecil di bawah nama brand. Boleh dikosongkan.</p>
                @error('app_title')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-col gap-4 border-t border-slate-100 pt-4 dark:border-slate-800 sm:flex-row sm:items-start">
            <div class="shrink-0">
                @if ($branding['logo_url'])
                    <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['brand'] }}"
                         class="h-20 w-20 rounded-2xl bg-white object-contain p-2 ring-1 ring-slate-200 dark:ring-slate-700">
                @else
                    <div class="grid h-20 w-20 place-items-center rounded-2xl bg-emerald-600 text-xl font-black text-white">{{ $branding['inisial'] }}</div>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <label for="logo" class="label">Logo</label>
                <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.webp"
                       class="block w-full cursor-pointer rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white dark:border-slate-700 dark:bg-slate-900">
                <p class="hint">PNG, JPG, atau WEBP maksimal 2 MB. Disarankan gambar persegi (mis. 512×512).</p>
                @error('logo')<p class="error-text">{{ $message }}</p>@enderror
                @if ($branding['logo_url'])
                    <label class="mt-2 inline-flex cursor-pointer items-center gap-2 text-xs text-red-600 dark:text-red-400">
                        <input type="checkbox" name="hapus_logo" value="1" class="h-4 w-4 rounded border-slate-300 text-red-600">
                        Hapus logo dan kembali ke inisial
                    </label>
                @endif
            </div>
        </div>

        <div class="flex gap-2">
            <button class="btn-primary">Simpan identitas</button>
            <a href="{{ route('dashboard') }}" class="btn-secondary">Kembali</a>
        </div>
    </form>

    <form method="POST" action="{{ route('pengaturan.update') }}" class="card card-pad max-w-4xl space-y-5">
        @csrf
        @method('PUT')

        <div>
            <h2 class="section-title">Parameter bisnis</h2>
            <p class="hint mt-1">Batas transaksi, toleransi selisih, dan periode akuntansi.</p>
        </div>

        @foreach ($defaults as $key => $meta)
            <div class="grid gap-2 border-b border-slate-100 pb-4 last:border-0 last:pb-0 dark:border-slate-800 sm:grid-cols-3 sm:gap-4">
                <div>
                    <label for="{{ $key }}" class="label">{{ str($key)->replace('_', ' ')->title() }}</label>
                    <p class="text-[11px] text-slate-400">{{ $key }}</p>
                </div>
                <div>
                    <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, $nilai[$key] ?? $meta['value']) }}" required
                           inputmode="{{ $meta['tipe'] === 'integer' ? 'numeric' : 'decimal' }}"
                           @isset($meta['min']) min="{{ $meta['min'] }}" @endisset
                           @isset($meta['max']) max="{{ $meta['max'] }}" @endisset
                           class="input @error($key) input-error @enderror">
                    @if (isset($meta['min']) || isset($meta['max']))
                        <p class="mt-1 text-[11px] text-slate-400">Rentang: {{ $meta['min'] ?? 0 }} – {{ $meta['max'] ?? '∞' }}</p>
                    @endif
                    @error($key)<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <p class="hint self-center">{{ $meta['deskripsi'] }}</p>
            </div>
        @endforeach

        <div class="flex gap-2">
            <button class="btn-primary">Simpan pengaturan</button>
            <a href="{{ route('dashboard') }}" class="btn-secondary">Kembali</a>
        </div>
    </form>
@endsection
