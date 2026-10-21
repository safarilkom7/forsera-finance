@extends('layouts.app')

@section('title', 'Rekonsiliasi')
@section('subtitle', 'Cocokkan catatan dengan dana yang benar-benar diterima')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="muted">Rekonsiliasi dilakukan per unit × metode × rentang tanggal.</p>
        <a href="{{ route('rekonsiliasi.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Rekonsiliasi baru</a>
    </div>

    <form method="GET" action="{{ route('rekonsiliasi.index') }}" class="card mb-5 card-pad">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="dari" class="label">Dari</label>
                <input type="date" id="dari" name="dari" value="{{ $filter['dari'] }}" class="input">
            </div>
            <div>
                <label for="sampai" class="label">Sampai</label>
                <input type="date" id="sampai" name="sampai" value="{{ $filter['sampai'] }}" class="input">
            </div>
            <div>
                <label for="unit" class="label">Unit</label>
                <select id="unit" name="unit" class="input">
                    <option value="">Semua unit</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected($filter['unit'] === $unit->id)>{{ $unit->nama }}</option>
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
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="input">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filter['status'] === $status->value)>{{ $status->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button class="btn-primary">Terapkan</button>
            <a href="{{ route('rekonsiliasi.index') }}" class="btn-secondary">Reset</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            @forelse ($data as $rek)
                <a href="{{ route('rekonsiliasi.show', $rek) }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold">{{ $rek->kode }}</p>
                        <x-status-badge :status="$rek->status" />
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $rek->unitUsaha?->nama }} · {{ $rek->metode?->nama }}</p>
                    <p class="text-xs text-slate-500">{{ format_tanggal($rek->periode_mulai) }} – {{ format_tanggal($rek->periode_selesai) }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-sm text-slate-500">Selisih</span>
                        <span class="font-bold {{ $rek->selisih < 0 ? 'text-red-600' : ($rek->selisih > 0 ? 'text-orange-700' : 'text-emerald-700') }}">{{ \App\Support\Format::rupiahSigned($rek->selisih) }}</span>
                    </div>
                </a>
            @empty
                <p class="p-8 text-center text-sm text-slate-500">Belum ada rekonsiliasi.</p>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="table">
                <thead>
                <tr>
                    <th>Kode</th>
                    <th>Unit / Metode</th>
                    <th>Periode</th>
                    <th class="text-right">Dicatat</th>
                    <th class="text-right">Diterima</th>
                    <th class="text-right">Selisih</th>
                    <th>Status</th>
                    <th>Verifikator</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $rek)
                    <tr>
                        <td class="font-semibold">{{ $rek->kode }}</td>
                        <td>
                            <p>{{ $rek->unitUsaha?->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $rek->metode?->nama }}</p>
                        </td>
                        <td class="whitespace-nowrap text-xs">{{ format_tanggal($rek->periode_mulai) }} – {{ format_tanggal($rek->periode_selesai) }}</td>
                        <td class="text-right">{{ format_rupiah($rek->total_dicatat) }}</td>
                        <td class="text-right">{{ format_rupiah($rek->total_diterima) }}</td>
                        <td class="text-right font-semibold {{ $rek->selisih < 0 ? 'text-red-600' : ($rek->selisih > 0 ? 'text-orange-700' : 'text-emerald-700') }}">{{ \App\Support\Format::rupiahSigned($rek->selisih) }}</td>
                        <td><x-status-badge :status="$rek->status" /></td>
                        <td class="text-xs">{{ $rek->diverifikasiOleh?->name ?? '—' }}</td>
                        <td class="text-right"><a href="{{ route('rekonsiliasi.show', $rek) }}" class="btn-ghost btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-10 text-center text-slate-500">Belum ada rekonsiliasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
@endsection
