@extends('layouts.app')

@section('title', 'Pengeluaran')
@section('subtitle', 'Belanja, gaji, sewa, dan biaya operasional')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="muted">Filter periode {{ format_tanggal($filter['dari']) }} – {{ format_tanggal($filter['sampai']) }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ format_rupiah($total) }} <span class="text-sm font-normal text-slate-500">total pengeluaran disetujui</span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pengeluaran.export', request()->query()) }}" class="btn-secondary"><x-icon name="download" class="h-4 w-4" /> Excel</a>
            <a href="{{ route('pengeluaran.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Pengeluaran</a>
        </div>
    </div>

    <form method="GET" action="{{ route('pengeluaran.index') }}" class="card mb-5 card-pad">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="dari" class="label">Dari tanggal</label>
                <input type="date" id="dari" name="dari" value="{{ $filter['dari'] }}" class="input">
            </div>
            <div>
                <label for="sampai" class="label">Sampai tanggal</label>
                <input type="date" id="sampai" name="sampai" value="{{ $filter['sampai'] }}" class="input">
            </div>
            <div>
                <label for="unit" class="label">Unit usaha</label>
                <select id="unit" name="unit" class="input">
                    <option value="">Semua unit</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected($filter['unit'] === $unit->id)>{{ $unit->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="jenis" class="label">Jenis</label>
                <select id="jenis" name="jenis" class="input">
                    <option value="">Semua jenis</option>
                    @foreach ($jenises as $jenis)
                        <option value="{{ $jenis->id }}" @selected($filter['jenis'] === $jenis->id)>{{ $jenis->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="metode" class="label">Metode</label>
                <select id="metode" name="metode" class="input">
                    <option value="">Semua metode</option>
                    @foreach ($metodes as $metode)
                        <option value="{{ $metode->id }}" @selected($filter['metode'] === $metode->id)>{{ $metode->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="label">Persetujuan</label>
                <select id="status" name="status" class="input">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filter['status'] === $status->value)>{{ $status->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="q" class="label">Cari catatan</label>
                <input type="search" id="q" name="q" value="{{ $filter['q'] }}" class="input" placeholder="Contoh: gas elpiji">
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="submit" class="btn-primary">Terapkan filter</button>
            <a href="{{ route('pengeluaran.index') }}" class="btn-secondary">Reset</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            @forelse ($data as $item)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $item->jenis?->nama }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $item->unitUsaha?->nama }} · {{ format_tanggal($item->tanggal) }}</p>
                        </div>
                        <x-status-badge :status="$item->status_persetujuan" />
                    </div>
                    <p class="mt-2 text-lg font-bold">{{ format_rupiah($item->total_nominal) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $item->metode?->nama }} · {{ $item->dari_kas_harian ? 'dari kas harian' : 'bukan kas harian' }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @can('update', $item)
                            <a href="{{ route('pengeluaran.edit', $item) }}" class="btn-secondary btn-sm">Ubah</a>
                        @endcan
                        @can('setujui', $item)
                            @if ($item->butuhPersetujuan())
                                <form method="POST" action="{{ route('pengeluaran.setujui', $item) }}">@csrf<button class="btn-primary btn-sm">Setujui</button></form>
                                <form method="POST" action="{{ route('pengeluaran.tolak', $item) }}" data-confirm="Tolak pengeluaran ini?">@csrf<button class="btn-ghost btn-sm text-red-600">Tolak</button></form>
                            @endif
                        @endcan
                        @can('delete', $item)
                            <form method="POST" action="{{ route('pengeluaran.destroy', $item) }}" data-confirm="Hapus pengeluaran ini?">@csrf @method('DELETE')<button class="btn-ghost btn-sm text-red-600">Hapus</button></form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <p class="font-semibold">Belum ada pengeluaran.</p>
                    <p class="mt-1 text-sm text-slate-500">Tambahkan pengeluaran atau ubah filter.</p>
                </div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="table">
                <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Unit / Jenis</th>
                    <th>Metode</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                    <th>Kas</th>
                    <th>Dibuat oleh</th>
                    <th class="text-right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $item)
                    <tr>
                        <td class="whitespace-nowrap">{{ format_tanggal($item->tanggal) }}</td>
                        <td>
                            <p class="font-medium text-slate-900 dark:text-slate-100">{{ $item->unitUsaha?->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $item->jenis?->nama }}</p>
                        </td>
                        <td>{{ $item->metode?->nama }}</td>
                        <td class="whitespace-nowrap text-right font-semibold">{{ format_rupiah($item->total_nominal) }}</td>
                        <td><x-status-badge :status="$item->status_persetujuan" /></td>
                        <td class="text-xs">{{ $item->dari_kas_harian ? 'Kas harian' : '—' }}</td>
                        <td class="whitespace-nowrap text-xs">{{ $item->dibuatOleh?->name }}</td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex justify-end gap-1">
                                @can('update', $item)
                                    <a href="{{ route('pengeluaran.edit', $item) }}" class="btn-ghost btn-sm">Ubah</a>
                                @endcan
                                @can('setujui', $item)
                                    @if ($item->butuhPersetujuan())
                                        <form method="POST" action="{{ route('pengeluaran.setujui', $item) }}">@csrf<button class="btn-ghost btn-sm text-emerald-600">Setujui</button></form>
                                        <form method="POST" action="{{ route('pengeluaran.tolak', $item) }}" data-confirm="Tolak pengeluaran ini?">@csrf<button class="btn-ghost btn-sm text-red-600">Tolak</button></form>
                                    @endif
                                @endcan
                                @can('delete', $item)
                                    <form method="POST" action="{{ route('pengeluaran.destroy', $item) }}" data-confirm="Hapus pengeluaran ini?">@csrf @method('DELETE')<button class="btn-ghost btn-sm text-red-600">Hapus</button></form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-slate-500">Belum ada pengeluaran pada filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
@endsection
