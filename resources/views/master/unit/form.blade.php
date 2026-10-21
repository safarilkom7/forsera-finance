@extends('layouts.app')

@section('title', $unit ? 'Ubah Unit Usaha' : 'Unit Usaha Baru')

@section('content')
    <form method="POST" action="{{ $unit ? route('master.unit.update', $unit) : route('master.unit.store') }}" class="card card-pad max-w-2xl space-y-4">
        @csrf
        @if ($unit) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="kode" class="label">Kode unit</label>
                <input id="kode" name="kode" value="{{ old('kode', $unit?->kode) }}" required maxlength="20" class="input uppercase @error('kode') input-error @enderror" placeholder="FRS">
                @error('kode')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="tipe" class="label">Tipe usaha</label>
                <select id="tipe" name="tipe" required class="input @error('tipe') input-error @enderror">
                    @foreach (\App\Enums\TipeUnitUsaha::cases() as $tipe)
                        <option value="{{ $tipe->value }}" @selected(old('tipe', $unit?->tipe?->value) === $tipe->value)>{{ $tipe->getLabel() }}</option>
                    @endforeach
                </select>
                @error('tipe')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="nama" class="label">Nama unit</label>
            <input id="nama" name="nama" value="{{ old('nama', $unit?->nama) }}" required maxlength="100" class="input @error('nama') input-error @enderror" placeholder="FORSERA RESTO">
            @error('nama')<p class="error-text">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $unit?->aktif ?? true)) class="h-4 w-4 rounded border-slate-300 text-emerald-700">
            Unit aktif dan bisa dipilih pada transaksi
        </label>

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">Simpan</button>
            <a href="{{ route('master.unit.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
