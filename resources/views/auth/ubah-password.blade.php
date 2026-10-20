@extends('layouts.guest')

@section('title', 'Ganti Password')

@section('content')
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
        <x-icon name="lock" class="mt-0.5 h-5 w-5 shrink-0" />
        <div>
            <p class="font-semibold">Ganti password terlebih dahulu</p>
            <p class="mt-0.5 text-amber-700 dark:text-amber-300/80">Demi keamanan, Anda harus mengganti password sebelum memakai aplikasi.</p>
        </div>
    </div>

    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Password baru</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Masuk sebagai {{ auth()->user()->email }}.</p>

    <form method="POST" action="{{ route('password.ubah') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="password_lama" class="label">Password saat ini</label>
            <input id="password_lama" name="password_lama" type="password" autocomplete="current-password" required
                   class="input @error('password_lama') input-error @enderror">
            @error('password_lama')<p class="error-text">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="label">Password baru</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required
                   class="input @error('password') input-error @enderror">
            <p class="hint">Minimal 8 karakter, terdiri dari huruf besar, huruf kecil, dan angka.</p>
            @error('password')<p class="error-text">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Ulangi password baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                   class="input">
        </div>

        <button type="submit" class="btn-primary w-full">Simpan password baru</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Keluar dari akun</button>
    </form>
@endsection
