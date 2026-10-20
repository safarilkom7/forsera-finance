@extends('layouts.app')

@section('title', 'Metode Pembayaran')
@section('subtitle', 'Tunai, QRIS, dan transfer bank')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Tipe metode menentukan perilaku rekonsiliasi (kas tunai vs settlement non-tunai).</p>
        <a href="{{ route('master.metode.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Metode baru</a>
    </div>

    <div class="card overflow-hidden">
        <table class="table">
            <thead><tr><th>Nama</th><th>Tipe</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($data as $metode)
                <tr>
                    <td class="font-medium text-slate-900 dark:text-slate-100">{{ $metode->nama }}</td>
                    <td>{{ $metode->tipe?->getLabel() }}</td>
                    <td><span class="badge {{ $metode->aktif ? 'badge-green' : 'badge-gray' }}">{{ $metode->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="text-right"><a href="{{ route('master.metode.edit', $metode) }}" class="btn-ghost btn-sm">Ubah</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-8 text-center text-slate-500">Belum ada metode.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
