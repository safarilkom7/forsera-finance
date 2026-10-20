@extends('layouts.app')

@section('title', 'Satuan')
@section('subtitle', 'Satuan default untuk item dan detail pengeluaran')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Gunakan singkatan yang konsisten agar laporan mudah dibaca.</p>
        <a href="{{ route('master.satuan.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Satuan baru</a>
    </div>

    <div class="card overflow-hidden">
        <table class="table">
            <thead><tr><th>Nama</th><th>Singkatan</th><th></th></tr></thead>
            <tbody>
            @forelse ($data as $satuan)
                <tr>
                    <td class="font-medium text-slate-900 dark:text-slate-100">{{ $satuan->nama }}</td>
                    <td><span class="badge badge-gray">{{ $satuan->singkatan }}</span></td>
                    <td class="text-right"><a href="{{ route('master.satuan.edit', $satuan) }}" class="btn-ghost btn-sm">Ubah</a></td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-8 text-center text-slate-500">Belum ada satuan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
