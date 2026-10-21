@extends('layouts.app')

@section('title', 'Pemasukan')
@section('subtitle', 'Catatan uang masuk dari tiga unit usaha')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="muted">Filter periode {{ format_tanggal($filter['dari']) }} – {{ format_tanggal($filter['sampai']) }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ format_rupiah($total) }} <span class="text-sm font-normal text-slate-500">total pemasukan terfilter</span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pemasukan.export', request()->query()) }}" class="btn-secondary">
                <x-icon name="download" class="h-4 w-4" /> Excel
            </a>
            <a href="{{ route('pemasukan.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Pemasukan
            </a>
        </div>
    </div>

    <div class="mb-5 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs text-sky-900 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">
        <p class="font-semibold">Apa arti status “Belum Dicek”?</p>
        <p class="mt-1 leading-relaxed">
            Transaksi sudah dicatat, tetapi uangnya belum dicocokkan dengan yang benar-benar diterima
            (kas, QRIS, atau transfer). Pencocokan dilakukan lewat menu <strong>Rekonsiliasi</strong> oleh
            <strong>Owner</strong> atau <strong>Admin Keuangan</strong>. Tidak perlu “approve” per baris:
            begitu rekonsiliasi disimpan, status berubah menjadi Sesuai / Selisih / Selesai beserta nama pemeriksanya.
            Pembuat transaksi tidak boleh mencocokkan transaksinya sendiri (maker-checker), kecuali Owner.
        </p>
        @if ($boleh_verifikasi)
            <a href="{{ route('rekonsiliasi.create') }}" class="mt-2 inline-flex font-semibold text-sky-700 hover:underline dark:text-sky-300">Mulai rekonsiliasi →</a>
        @endif
    </div>

    <form method="GET" action="{{ route('pemasukan.index') }}" class="card mb-5 card-pad">
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
                <label for="metode" class="label">Metode</label>
                <select id="metode" name="metode" class="input">
                    <option value="">Semua metode</option>
                    @foreach ($metodes as $metode)
                        <option value="{{ $metode->id }}" @selected($filter['metode'] === $metode->id)>{{ $metode->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="kategori" class="label">Kategori</label>
                <select id="kategori" name="kategori" class="input">
                    <option value="">Semua kategori</option>
                    @foreach ($kategoris as $kategori)
                        <option value="{{ $kategori->id }}" @selected($filter['kategori'] === $kategori->id)>{{ $kategori->nama }}</option>
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
            @if ($pembuat->isNotEmpty())
                <div>
                    <label for="pembuat" class="label">Dibuat oleh</label>
                    <select id="pembuat" name="pembuat" class="input">
                        <option value="">Semua pengguna</option>
                        @foreach ($pembuat as $user)
                            <option value="{{ $user->id }}" @selected($filter['pembuat'] === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label for="q" class="label">Cari catatan</label>
                <input type="search" id="q" name="q" value="{{ $filter['q'] }}" class="input" placeholder="Contoh: meja 4">
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="submit" class="btn-primary">Terapkan filter</button>
            <a href="{{ route('pemasukan.index') }}" class="btn-secondary">Reset</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            @forelse ($data as $item)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $item->kategori?->nama }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $item->unitUsaha?->nama }} · {{ format_tanggal($item->tanggal) }}</p>
                        </div>
                        <x-status-badge :status="$item->status_verifikasi" />
                    </div>
                    <p class="mt-2 text-lg font-bold text-slate-900 dark:text-white">{{ format_rupiah($item->nominal) }}</p>
                    @if ($item->rekonsiliasi)
                        <p class="mt-1 text-[11px] text-slate-500">{{ $item->rekonsiliasi->kode }} · dicek {{ $item->rekonsiliasi->diverifikasiOleh?->name ?? '—' }}</p>
                    @endif
                    <p class="mt-1 text-xs text-slate-500">{{ $item->metode?->nama }} · oleh {{ $item->dibuatOleh?->name }}</p>
                    @if ($item->note)
                        <p class="mt-1 text-xs text-slate-500">{{ $item->note }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($boleh_verifikasi && $item->status_verifikasi === \App\Enums\StatusVerifikasi::BELUM_DICEK)
                            <a href="{{ route('rekonsiliasi.create', ['unit' => $item->unit_usaha_id, 'metode' => $item->metode_pembayaran_id, 'mulai' => $item->tanggal->toDateString(), 'selesai' => now()->toDateString()]) }}" class="btn-secondary btn-sm text-emerald-700">Verifikasi</a>
                        @endif
                        @can('update', $item)
                            <a href="{{ route('pemasukan.edit', $item) }}" class="btn-secondary btn-sm">{{ $item->terkunci() ? 'Koreksi' : 'Ubah' }}</a>
                        @endcan
                        @can('delete', $item)
                            <form method="POST" action="{{ route('pemasukan.destroy', $item) }}"
                                  data-confirm="Pemasukan {{ format_rupiah($item->nominal) }} akan dihapus permanen dan tercatat di audit log."
                                  data-confirm-title="Hapus pemasukan?"
                                  data-confirm-severity="danger"
                                  data-confirm-label="Ya, hapus">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost btn-sm text-red-600">Hapus</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <p class="font-semibold">Belum ada pemasukan pada filter ini.</p>
                    <p class="mt-1 text-sm text-slate-500">Ubah periode atau tambahkan pemasukan baru.</p>
                </div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="table">
                <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Unit / Kategori</th>
                    <th>Metode</th>
                    <th class="text-right">Nominal</th>
                    <th>Status</th>
                    <th>Catatan</th>
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
                            <p class="text-xs text-slate-500">{{ $item->kategori?->nama }}</p>
                        </td>
                        <td>{{ $item->metode?->nama }}</td>
                        <td class="whitespace-nowrap text-right font-semibold">{{ format_rupiah($item->nominal) }}</td>
                        <td>
                            <x-status-badge :status="$item->status_verifikasi" />
                            @if ($item->rekonsiliasi)
                                <p class="mt-1 whitespace-nowrap text-[11px] text-slate-500">
                                    {{ $item->rekonsiliasi->kode }} · dicek {{ $item->rekonsiliasi->diverifikasiOleh?->name ?? '—' }}
                                </p>
                            @endif
                        </td>
                        <td class="max-w-[14rem] truncate">{{ $item->note ?: '—' }}</td>
                        <td class="whitespace-nowrap text-xs">{{ $item->dibuatOleh?->name }}</td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex justify-end gap-1">
                                @if ($boleh_verifikasi && $item->status_verifikasi === \App\Enums\StatusVerifikasi::BELUM_DICEK)
                                    <a href="{{ route('rekonsiliasi.create', ['unit' => $item->unit_usaha_id, 'metode' => $item->metode_pembayaran_id, 'mulai' => $item->tanggal->toDateString(), 'selesai' => now()->toDateString()]) }}"
                                       class="btn-ghost btn-sm text-emerald-600" title="Cocokkan dengan dana yang diterima">Verifikasi</a>
                                @endif
                                @can('update', $item)
                                    <a href="{{ route('pemasukan.edit', $item) }}" class="btn-ghost btn-sm">{{ $item->terkunci() ? 'Koreksi' : 'Ubah' }}</a>
                                @endcan
                                @can('delete', $item)
                                    <form method="POST" action="{{ route('pemasukan.destroy', $item) }}"
                                          data-confirm="Pemasukan {{ format_rupiah($item->nominal) }} akan dihapus permanen dan tercatat di audit log."
                                          data-confirm-title="Hapus pemasukan?"
                                          data-confirm-severity="danger"
                                          data-confirm-label="Ya, hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost btn-sm text-red-600">Hapus</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-500">Belum ada pemasukan pada filter ini.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
@endsection
