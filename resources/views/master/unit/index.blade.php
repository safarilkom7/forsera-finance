@extends('layouts.app')

@section('title', 'Unit Usaha')
@section('subtitle', 'Tiga unit bisnis '.$branding['brand'])

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Menonaktifkan unit lebih aman daripada menghapus data historis.</p>
        <a href="{{ route('master.unit.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Unit baru</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Status</th><th class="text-right">Pemasukan</th><th class="text-right">Pengeluaran</th><th></th></tr></thead>
                <tbody>
                @forelse ($data as $unit)
                    <tr>
                        <td class="font-mono text-xs font-semibold">{{ $unit->kode }}</td>
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $unit->nama }}</td>
                        <td>{{ $unit->tipe?->getLabel() }}</td>
                        <td><span class="badge {{ $unit->aktif ? 'badge-green' : 'badge-gray' }}">{{ $unit->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right">{{ $unit->pemasukan_count }}</td>
                        <td class="text-right">{{ $unit->pengeluaran_count }}</td>
                        <td class="text-right"><a href="{{ route('master.unit.edit', $unit) }}" class="btn-ghost btn-sm">Ubah</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-slate-500">Belum ada unit usaha.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
