@extends('layouts.app')

@section('title', $item ? 'Ubah Item' : 'Item Baru')
@section('subtitle', $item?->nama)

@section('content')
    <div class="grid gap-5 xl:grid-cols-3">
        <form method="POST" action="{{ $item ? route('master.item.update', $item) : route('master.item.store') }}" class="card card-pad space-y-4 xl:col-span-2">
            @csrf
            @if ($item) @method('PUT') @endif

            <div>
                <label for="nama" class="label">Nama item</label>
                <input id="nama" name="nama" value="{{ old('nama', $item?->nama) }}" required maxlength="100" class="input @error('nama') input-error @enderror" placeholder="Biji kopi arabika">
                @error('nama')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="satuan_default_id" class="label">Satuan default</label>
                    <x-pilihan id="satuan_default_id" name="satuan_default_id" required searchable class="input">
                        @foreach ($satuans as $satuan)
                            <option value="{{ $satuan->id }}" @selected((int) old('satuan_default_id', $item?->satuan_default_id) === $satuan->id)>{{ $satuan->nama }} ({{ $satuan->singkatan }})</option>
                        @endforeach
                    </x-pilihan>
                    @error('satuan_default_id')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jenis_pengeluaran_id" class="label">Jenis pengeluaran</label>
                    <x-pilihan id="jenis_pengeluaran_id" name="jenis_pengeluaran_id" class="input">
                        <option value="">— Tidak terkait —</option>
                        @foreach ($jenises as $jenis)
                            <option value="{{ $jenis->id }}" @selected((int) old('jenis_pengeluaran_id', $item?->jenis_pengeluaran_id) === $jenis->id)>{{ $jenis->nama }}</option>
                        @endforeach
                    </x-pilihan>
                </div>
            </div>

            <div>
                <label for="harga_terakhir" class="label">Harga terakhir</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-500 dark:text-slate-400">Rp</span>
                    <input id="harga_terakhir" name="harga_terakhir" data-rupiah value="{{ old('harga_terakhir', $item?->harga_terakhir) }}" class="input pl-10" placeholder="0">
                </div>
                <p class="hint">Terisi otomatis setiap kali item disimpan pada pengeluaran.</p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $item?->aktif ?? true)) class="h-4 w-4 rounded border-slate-300 text-emerald-700">
                Item aktif dan bisa dipilih
            </label>

            <div class="flex gap-2 pt-2">
                <button class="btn-primary">Simpan</button>
                <a href="{{ route('master.item.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>

        @if ($item)
            <div class="card overflow-hidden xl:col-span-1">
                <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    <h2 class="section-title">Riwayat harga</h2>
                    <p class="hint mt-1">20 pembelian terakhir. Rata-rata 5 pembelian: {{ $rataRata ? format_rupiah($rataRata) : '—' }}</p>
                </div>
                <div class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                    @forelse ($riwayat as $baris)
                        <div class="px-4 py-2.5 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold">{{ format_rupiah($baris->harga_satuan) }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ format_tanggal($baris->pengeluaran?->tanggal) }}</span>
                            </div>
                            <p class="text-xs text-slate-500">{{ $baris->pengeluaran?->unitUsaha?->nama }} · {{ \App\Support\Format::angka($baris->jumlah) }} {{ $baris->satuan?->singkatan }}</p>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-slate-500">Belum ada riwayat pembelian.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
@endsection
