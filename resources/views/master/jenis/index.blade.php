@extends('layouts.app')

@section('title', 'Jenis Pengeluaran')
@section('subtitle', 'Bahan baku, gaji, sewa, royalti, dan lainnya')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Nonaktifkan jenis yang tidak dipakai; data lama tetap tersimpan.</p>
        <a href="{{ route('master.jenis.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Jenis baru</a>
    </div>

    <div class="card overflow-hidden">
        <table class="table">
            <thead><tr><th>Nama</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($data as $jenis)
                <tr>
                    <td class="font-medium text-slate-900 dark:text-slate-100">{{ $jenis->nama }}</td>
                    <td><span class="badge {{ $jenis->aktif ? 'badge-green' : 'badge-gray' }}">{{ $jenis->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="text-right"><a href="{{ route('master.jenis.edit', $jenis) }}" class="btn-ghost btn-sm">Ubah</a></td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-8 text-center text-slate-500">Belum ada jenis pengeluaran.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
