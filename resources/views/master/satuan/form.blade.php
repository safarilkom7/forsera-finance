@extends('layouts.app')

@section('title', $satuan ? 'Ubah Satuan' : 'Satuan Baru')

@section('content')
    <form method="POST" action="{{ $satuan ? route('master.satuan.update', $satuan) : route('master.satuan.store') }}" class="card card-pad max-w-xl space-y-4">
        @csrf
        @if ($satuan) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="nama" class="label">Nama satuan</label>
                <input id="nama" name="nama" value="{{ old('nama', $satuan?->nama) }}" required maxlength="50" class="input @error('nama') input-error @enderror" placeholder="Kilogram">
                @error('nama')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="singkatan" class="label">Singkatan</label>
                <input id="singkatan" name="singkatan" value="{{ old('singkatan', $satuan?->singkatan) }}" required maxlength="20" class="input @error('singkatan') input-error @enderror" placeholder="kg">
                @error('singkatan')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">Simpan</button>
            <a href="{{ route('master.satuan.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
