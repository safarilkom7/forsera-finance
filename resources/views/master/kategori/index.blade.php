@extends('layouts.app')

@section('title', 'Kategori Pemasukan')
@section('subtitle', 'Kategori per unit atau untuk semua unit')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Kategori global (tanpa unit) bisa dipakai di semua unit. Contoh: Pendapatan Lain-lain.</p>
        <a href="{{ route('master.kategori.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Kategori baru</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Nama</th><th>Unit</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($data as $kategori)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $kategori->nama }}</td>
                        <td>{{ $kategori->unitUsaha?->nama ?? 'Semua unit' }}</td>
                        <td><span class="badge {{ $kategori->aktif ? 'badge-green' : 'badge-gray' }}">{{ $kategori->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right"><a href="{{ route('master.kategori.edit', $kategori) }}" class="btn-ghost btn-sm">Ubah</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-slate-500">Belum ada kategori.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
