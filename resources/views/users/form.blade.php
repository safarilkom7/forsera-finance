@extends('layouts.app')

@section('title', $user ? 'Ubah Pengguna' : 'Pengguna Baru')
@section('subtitle', $user?->email)

@section('content')
    @php
        $roleTerpilih = old('role', $user?->roles->first()?->name ?? 'staf_unit');
        $unitTerpilih = collect(old('units', $user?->unitUsaha->pluck('id')->all() ?? []))->map(fn ($v) => (int) $v)->all();
    @endphp

    <form method="POST" action="{{ $user ? route('users.update', $user) : route('users.store') }}" class="card card-pad max-w-2xl space-y-4">
        @csrf
        @if ($user) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="label">Nama lengkap</label>
                <input id="name" name="name" value="{{ old('name', $user?->name) }}" required maxlength="120" class="input @error('name') input-error @enderror">
                @error('name')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required maxlength="190" class="input @error('email') input-error @enderror">
                @error('email')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="role" class="label">Peran</label>
                <select id="role" name="role" required class="input @error('role') input-error @enderror">
                    <option value="owner" @selected($roleTerpilih === 'owner')>Owner — akses penuh</option>
                    <option value="admin_keuangan" @selected($roleTerpilih === 'admin_keuangan')>Admin Keuangan</option>
                    <option value="staf_unit" @selected($roleTerpilih === 'staf_unit')>Staf Unit</option>
                </select>
                @error('role')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <x-password-input name="password"
                              :label="'Password '.($user ? 'baru (opsional)' : 'awal')"
                              autocomplete="new-password" :required="false"
                              :hint="$user ? 'Kosongkan bila tidak ingin mengganti. Bila diisi, pengguna wajib mengganti saat login berikutnya.' : 'Minimal 8 karakter, ada huruf besar, huruf kecil, dan angka.'" />
        </div>

        <x-password-input name="password_confirmation" label="Ulangi password"
                          autocomplete="new-password" :required="false" />

        <div id="unit-wrapper">
            <span class="label">Penugasan unit (untuk Staf Unit)</span>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($units as $unit)
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-slate-700">
                        <input type="checkbox" name="units[]" value="{{ $unit->id }}" @checked(in_array($unit->id, $unitTerpilih, true)) class="h-4 w-4 rounded border-slate-300 text-emerald-700">
                        {{ $unit->nama }}
                    </label>
                @endforeach
            </div>
            @error('units')<p class="error-text">{{ $message }}</p>@enderror
            <p class="hint">Staf hanya bisa melihat dan menginput transaksi untuk unit yang dicentang.</p>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $user?->aktif ?? true)) class="h-4 w-4 rounded border-slate-300 text-emerald-700">
            Pengguna aktif
        </label>

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">Simpan</button>
            <a href="{{ route('users.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const role = document.getElementById('role');
                const unit = document.getElementById('unit-wrapper');
                const sync = () => unit.classList.toggle('hidden', role.value !== 'staf_unit');
                role.addEventListener('change', sync);
                sync();
            });
        </script>
    @endpush
@endsection
