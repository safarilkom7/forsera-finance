@extends('layouts.app')

@section('title', 'Profil & Password')
@section('subtitle', $user->email)

@section('content')
    <div class="grid gap-5 xl:grid-cols-3">
        <form method="POST" action="{{ route('profil.update') }}" class="card card-pad max-w-2xl space-y-4 xl:col-span-2">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="label">Nama</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" class="input @error('name') input-error @enderror">
                    @error('name')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="190" class="input @error('email') input-error @enderror">
                    @error('email')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                <h2 class="font-semibold">Ganti password</h2>
                <p class="hint">Kosongkan bila tidak ingin mengganti password.</p>
            </div>

            <x-password-input name="password_lama" label="Password saat ini"
                              autocomplete="current-password" :required="false" />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-password-input name="password" label="Password baru"
                                  autocomplete="new-password" :required="false" />
                <x-password-input name="password_confirmation" label="Ulangi password baru"
                                  autocomplete="new-password" :required="false" />
            </div>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('dashboard') }}" class="btn-secondary">Batal</a>
            </div>
        </form>

        <div class="card card-pad h-fit">
            <h2 class="section-title">Akun Anda</h2>
            <dl class="mt-3 space-y-3 text-sm">
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Peran</dt><dd class="font-medium capitalize">{{ str_replace('_', ' ', $user->getRoleNames()->first() ?? '-') }}</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Unit ditugaskan</dt><dd class="font-medium">{{ $user->unitUsaha->pluck('nama')->join(', ') ?: 'Semua unit' }}</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Login terakhir</dt><dd class="font-medium">{{ $user->last_login_at ? format_tanggal($user->last_login_at).' '.$user->last_login_at->format('H:i') : '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500 dark:text-slate-400">Zona waktu</dt><dd class="font-medium">Asia/Jakarta</dd></div>
            </dl>
        </div>
    </div>
@endsection
