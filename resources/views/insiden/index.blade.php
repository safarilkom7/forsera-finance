@extends('layouts.app')

@section('title', 'Insiden Selisih')
@section('subtitle', 'Tindak lanjut setiap selisih rekonsiliasi')

@section('content')
    <form method="GET" action="{{ route('insiden.index') }}" class="card mb-5 card-pad">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="input">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filter['status'] === $status->value)>{{ $status->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="jenis" class="label">Jenis</label>
                <select id="jenis" name="jenis" class="input">
                    <option value="">Semua jenis</option>
                    @foreach ($jenises as $jenis)
                        <option value="{{ $jenis->value }}" @selected($filter['jenis'] === $jenis->value)>{{ $jenis->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="arah" class="label">Arah</label>
                <select id="arah" name="arah" class="input">
                    <option value="">Keduanya</option>
                    <option value="KURANG" @selected($filter['arah'] === 'KURANG')>Kurang</option>
                    <option value="LEBIH" @selected($filter['arah'] === 'LEBIH')>Lebih</option>
                </select>
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
                <label for="q" class="label">Cari kode</label>
                <input type="search" id="q" name="q" value="{{ $filter['q'] }}" class="input" placeholder="INC-202610-0001">
            </div>
            <div class="flex items-end gap-2">
                <button class="btn-primary flex-1">Filter</button>
                <a href="{{ route('insiden.index') }}" class="btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            @forelse ($data as $insiden)
                <a href="{{ route('insiden.show', $insiden) }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold">{{ $insiden->kode }}</p>
                        <x-status-badge :status="$insiden->status" />
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $insiden->rekonsiliasi?->unitUsaha?->nama }} · {{ $insiden->rekonsiliasi?->metode?->nama }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-xs text-slate-500">{{ $insiden->jenis?->getLabel() }} · {{ $insiden->arah?->getLabel() }}</span>
                        <span class="font-bold {{ $insiden->arah?->value === 'KURANG' ? 'text-red-600' : 'text-orange-700' }}">{{ format_rupiah($insiden->nominal_selisih) }}</span>
                    </div>
                </a>
            @empty
                <p class="p-8 text-center text-sm text-slate-500">Tidak ada insiden.</p>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="table">
                <thead>
                <tr>
                    <th>Kode</th>
                    <th>Unit / Metode</th>
                    <th>Jenis</th>
                    <th>Arah</th>
                    <th class="text-right">Selisih</th>
                    <th class="text-right">Terselesaikan</th>
                    <th>Status</th>
                    <th>PJ</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $insiden)
                    <tr>
                        <td class="font-semibold">{{ $insiden->kode }}</td>
                        <td>
                            <p>{{ $insiden->rekonsiliasi?->unitUsaha?->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $insiden->rekonsiliasi?->metode?->nama }}</p>
                        </td>
                        <td class="text-xs">{{ $insiden->jenis?->getLabel() }}</td>
                        <td><x-status-badge :status="$insiden->arah" /></td>
                        <td class="text-right font-semibold">{{ format_rupiah($insiden->nominal_selisih) }}</td>
                        <td class="text-right">{{ format_rupiah($insiden->nominal_terselesaikan) }}</td>
                        <td><x-status-badge :status="$insiden->status" /></td>
                        <td class="text-xs">{{ $insiden->penanggungJawab?->name ?? '—' }}</td>
                        <td class="text-right"><a href="{{ route('insiden.show', $insiden) }}" class="btn-ghost btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-10 text-center text-slate-500">Tidak ada insiden pada filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
@endsection
