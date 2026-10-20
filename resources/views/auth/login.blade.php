@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Masuk ke aplikasi</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gunakan akun yang diberikan Owner atau Admin Keuangan.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" autocomplete="username" required autofocus
                   value="{{ old('email') }}"
                   class="input @error('email') input-error @enderror"
                   placeholder="nama@forsera.co.id">
            @error('email')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   class="input @error('password') input-error @enderror"
                   placeholder="••••••••">
            @error('password')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                   class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900">
            Ingat saya di perangkat ini
        </label>

        <button type="submit" class="btn-primary w-full">Masuk</button>
    </form>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
        Lupa password? Hubungi Owner untuk reset password. Pada login pertama, Anda wajib mengganti password sementara.
    </div>
@endsection
