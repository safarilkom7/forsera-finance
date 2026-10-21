@extends('layouts.app')

@section('title', $kategori ? 'Ubah Kategori Pemasukan' : 'Kategori Pemasukan Baru')

@section('content')
    <form method="POST" action="{{ $kategori ? route('master.kategori.update', $kategori) : route('master.kategori.store') }}" class="card card-pad max-w-xl space-y-4">
        @csrf
        @if ($kategori) @method('PUT') @endif

        <div>
            <label for="nama" class="label">Nama kategori</label>
            <input id="nama" name="nama" value="{{ old('nama', $kategori?->nama) }}" required maxlength="100" class="input @error('nama') input-error @enderror" placeholder="Dine-in">
            @error('nama')<p class="error-text">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="unit_usaha_id" class="label">Unit usaha</label>
            <select id="unit_usaha_id" name="unit_usaha_id" class="input">
                <option value="">Semua unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected((int) old('unit_usaha_id', $kategori?->unit_usaha_id) === $unit->id)>{{ $unit->nama }}</option>
                @endforeach
            </select>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $kategori?->aktif ?? true)) class="h-4 w-4 rounded border-slate-300 text-emerald-700">
            Kategori aktif
        </label>

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">Simpan</button>
            <a href="{{ route('master.kategori.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
