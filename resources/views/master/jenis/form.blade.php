@extends('layouts.app')

@section('title', $jenis ? 'Ubah Jenis Pengeluaran' : 'Jenis Pengeluaran Baru')

@section('content')
    <form method="POST" action="{{ $jenis ? route('master.jenis.update', $jenis) : route('master.jenis.store') }}" class="card card-pad max-w-xl space-y-4">
        @csrf
        @if ($jenis) @method('PUT') @endif

        <div>
            <label for="nama" class="label">Nama jenis</label>
            <input id="nama" name="nama" value="{{ old('nama', $jenis?->nama) }}" required maxlength="100" class="input @error('nama') input-error @enderror" placeholder="Bahan Baku">
            @error('nama')<p class="error-text">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $jenis?->aktif ?? true)) class="h-4 w-4 rounded border-slate-300 text-emerald-700">
            Jenis aktif
        </label>

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">Simpan</button>
            <a href="{{ route('master.jenis.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
