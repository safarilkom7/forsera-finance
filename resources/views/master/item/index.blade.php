@extends('layouts.app')

@section('title', 'Item')
@section('subtitle', 'Bahan, perlengkapan, dan barang yang dibeli')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Harga terakhir otomatis diperbarui dari detail pengeluaran.</p>
        <a href="{{ route('master.item.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Item baru</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Nama</th><th>Satuan default</th><th>Jenis</th><th class="text-right">Harga terakhir</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($data as $item)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $item->nama }}</td>
                        <td>{{ $item->satuanDefault?->singkatan }} <span class="text-xs text-slate-500 dark:text-slate-400">({{ $item->satuanDefault?->nama }})</span></td>
                        <td>{{ $item->jenisPengeluaran?->nama ?? '—' }}</td>
                        <td class="text-right">{{ $item->harga_terakhir ? format_rupiah($item->harga_terakhir) : '—' }}</td>
                        <td><span class="badge {{ $item->aktif ? 'badge-green' : 'badge-gray' }}">{{ $item->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right"><a href="{{ route('master.item.edit', $item) }}" class="btn-ghost btn-sm">Ubah</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada item.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
