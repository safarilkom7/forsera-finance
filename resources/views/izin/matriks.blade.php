@extends('layouts.app')

@section('title', 'Hak Akses: '.$user->name)
@section('subtitle', 'Izin khusus di atas peran bawaan')

@section('content')
    @php
        $terpilih = collect(old('izin', $khusus->all()))->filter(fn ($v) => is_string($v))->all();
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-base font-bold text-slate-900 dark:text-white">{{ $user->name }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
        </div>
        <div class="flex items-center gap-2">
            @foreach ($user->roles as $role)
                <span class="badge {{ $role->name === 'owner' ? 'badge-blue' : ($role->name === 'admin_keuangan' ? 'badge-green' : 'badge-gray') }}">{{ str_replace('_', ' ', $role->name) }}</span>
            @endforeach
            <a href="{{ route('izin.index') }}" class="btn-ghost btn-sm"><x-icon name="arrow-left" class="h-4 w-4" /> Kembali</a>
        </div>
    </div>

    <form method="POST" action="{{ route('izin.update', $user) }}">
        @csrf
        @method('PUT')

        <p class="hint mb-4">
            Izin abu-abu tercentang adalah bawaan peran dan tidak bisa dilepas di sini.
            Centang tambahan menjadi <strong>izin khusus</strong> milik pengguna ini; menghapus centangnya mengembalikan akses ke default peran.
        </p>

        @error('izin.*')<p class="error-text">{{ $message }}</p>@enderror

        <div class="space-y-4">
            @foreach ($grup as $namaGrup => $izinGrup)
                <div class="card card-pad">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-300">{{ $namaGrup }}</h3>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($izinGrup as $nama => $deskripsi)
                            @php
                                $dariPeranItem = $dariPeran->contains($nama);
                                $tercentang = in_array($nama, $terpilih, true);
                            @endphp
                            <label class="flex items-start gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm {{ $dariPeranItem ? 'bg-slate-50 text-slate-500 dark:bg-slate-800/60 dark:text-slate-400' : 'text-slate-700 dark:text-slate-200' }}">
                                <input type="checkbox" name="izin[]" value="{{ $nama }}"
                                       @checked($dariPeranItem || $tercentang) @disabled($dariPeranItem)
                                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-700">
                                <span class="min-w-0">
                                    <span class="block">{{ $deskripsi }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        @if ($dariPeranItem)
                                            Dari peran
                                        @else
                                            {{ $nama }}
                                            @unless (App\Support\PetaIzin::dipakaiRuntime($nama))
                                                — belum berpengaruh, akses masih dari peran
                                            @endunless
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex gap-2">
            <button type="submit" class="btn-primary">Simpan izin khusus</button>
            <a href="{{ route('izin.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
