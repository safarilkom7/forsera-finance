@extends('layouts.app')

@section('title', $metode ? 'Ubah Metode Pembayaran' : 'Metode Pembayaran Baru')

@section('content')
    <form method="POST" action="{{ $metode ? route('master.metode.update', $metode) : route('master.metode.store') }}" class="card card-pad max-w-xl space-y-4">
        @csrf
        @if ($metode) @method('PUT') @endif

        <div>
            <label for="nama" class="label">Nama metode</label>
            <input id="nama" name="nama" value="{{ old('nama', $metode?->nama) }}" required maxlength="50" class="input @error('nama') input-error @enderror" placeholder="QRIS">
            @error('nama')<p class="error-text">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="tipe" class="label">Tipe</label>
            <select id="tipe" name="tipe" required class="input">
                @foreach (\App\Enums\TipeMetodePembayaran::cases() as $tipe)
                    <option value="{{ $tipe->value }}" @selected(old('tipe', $metode?->tipe?->value) === $tipe->value)>{{ $tipe->getLabel() }}</option>
                @endforeach
            </select>
            <p class="hint">TUNAI memakai potongan kas harian; QRIS/TRANSFER memakai biaya admin dan menunggu settlement.</p>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $metode?->aktif ?? true)) class="h-4 w-4 rounded border-slate-300 text-emerald-700">
            Metode aktif
        </label>

        <div class="flex gap-2 pt-2">
            <button class="btn-primary">Simpan</button>
            <a href="{{ route('master.metode.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
