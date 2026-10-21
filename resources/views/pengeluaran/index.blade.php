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
                <x-tanggal id="dari" name="dari" value="{{ $filter['dari'] }}" class="input" />
            </div>
            <div>
                <label for="sampai" class="label">Sampai tanggal</label>
                <x-tanggal id="sampai" name="sampai" value="{{ $filter['sampai'] }}" class="input" />
            </div>
            <div>
                <label for="unit" class="label">Unit usaha</label>
                <x-pilihan id="unit" name="unit" class="input">
                    <option value="">Semua unit</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected($filter['unit'] === $unit->id)>{{ $unit->nama }}</option>
                    @endforeach
                </x-pilihan>
            </div>
            <div>
                <label for="jenis" class="label">Jenis</label>
                <x-pilihan id="jenis" name="jenis" class="input">
                    <option value="">Semua jenis</option>
                    @foreach ($jenises as $jenis)
                        <option value="{{ $jenis->id }}" @selected($filter['jenis'] === $jenis->id)>{{ $jenis->nama }}</option>
                    @endforeach
                </x-pilihan>
            </div>
            <div>
                <label for="metode" class="label">Metode</label>
                <x-pilihan id="metode" name="metode" class="input">
                    <option value="">Semua metode</option>
                    @foreach ($metodes as $metode)
                        <option value="{{ $metode->id }}" @selected($filter['metode'] === $metode->id)>{{ $metode->nama }}</option>
                    @endforeach
                </x-pilihan>
            </div>
            <div>
                <label for="status" class="label">Persetujuan</label>
                <x-pilihan id="status" name="status" class="input">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filter['status'] === $status->value)>{{ $status->getLabel() }}</option>
                    @endforeach
                </x-pilihan>
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

    <div class="card overflow-hidden" data-pilih-scope>
        @if ($boleh_hapus)
            <x-aksi-massal :aksi="route('pengeluaran.hapus-massal')" kata="pengeluaran" judul="Hapus pengeluaran terpilih?" />
        @endif

        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            @forelse ($data as $item)
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        @can('delete', $item)
                            <label class="inline-flex min-h-6 min-w-6 shrink-0 items-center justify-center">
                                <input type="checkbox" form="aksi-massal-form" name="id[]" value="{{ $item->id }}" data-pilih
                                       class="checkbox" aria-label="Pilih pengeluaran {{ $item->jenis?->nama }} {{ format_rupiah($item->total_nominal) }}">
                            </label>
                        @endcan
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
                            <a href="{{ route('pengeluaran.edit', $item) }}" class="btn-ghost btn-sm">Ubah</a>
                        @endcan
                        @can('setujui', $item)
                            @if ($item->butuhPersetujuan())
                                <form method="POST" action="{{ route('pengeluaran.setujui', $item) }}"
                                      data-confirm="Pengeluaran {{ format_rupiah($item->total_nominal) }} akan disetujui dan ikut dihitung sebagai biaya. Pastikan bukti dan nominalnya sudah benar."
                                      data-confirm-title="Setujui pengeluaran?"
                                      data-confirm-severity="info"
                                      data-confirm-label="Ya, setujui">@csrf<button class="btn-ghost-success btn-sm">Setujui</button></form>
                                <form method="POST" action="{{ route('pengeluaran.tolak', $item) }}" data-confirm="Pengeluaran {{ format_rupiah($item->total_nominal) }} akan ditolak. Pembuat dapat memperbaikinya."
                                          data-confirm-title="Tolak pengeluaran?"
                                          data-confirm-severity="warning"
                                          data-confirm-label="Ya, tolak">@csrf<button class="btn-ghost-warning btn-sm">Tolak</button></form>
                            @endif
                        @endcan
                        @can('delete', $item)
                            <form method="POST" action="{{ route('pengeluaran.destroy', $item) }}" data-confirm="Pengeluaran {{ format_rupiah($item->total_nominal) }} akan dihapus permanen dan tercatat di audit log."
                                          data-confirm-title="Hapus pengeluaran?"
                                          data-confirm-severity="danger"
                                          data-confirm-label="Ya, hapus">@csrf @method('DELETE')<button class="btn-ghost-danger btn-sm">Hapus</button></form>
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
                    @if ($boleh_hapus)
                        <th class="w-10">
                            <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                <input type="checkbox" data-pilih-semua class="checkbox" aria-label="Pilih semua pengeluaran di halaman ini">
                            </label>
                        </th>
                    @endif
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
                        @if ($boleh_hapus)
                            <td>
                                @can('delete', $item)
                                    <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                        <input type="checkbox" form="aksi-massal-form" name="id[]" value="{{ $item->id }}" data-pilih
                                               class="checkbox" aria-label="Pilih pengeluaran {{ $item->jenis?->nama }} {{ format_rupiah($item->total_nominal) }}">
                                    </label>
                                @else
                                    <span aria-hidden="true">—</span>
                                @endcan
                            </td>
                        @endif
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
                                        <form method="POST" action="{{ route('pengeluaran.setujui', $item) }}"
                                              data-confirm="Pengeluaran {{ format_rupiah($item->total_nominal) }} akan disetujui dan ikut dihitung sebagai biaya. Pastikan bukti dan nominalnya sudah benar."
                                              data-confirm-title="Setujui pengeluaran?"
                                              data-confirm-severity="info"
                                              data-confirm-label="Ya, setujui">@csrf<button class="btn-ghost-success btn-sm">Setujui</button></form>
                                        <form method="POST" action="{{ route('pengeluaran.tolak', $item) }}" data-confirm="Pengeluaran {{ format_rupiah($item->total_nominal) }} akan ditolak. Pembuat dapat memperbaikinya."
                                          data-confirm-title="Tolak pengeluaran?"
                                          data-confirm-severity="warning"
                                          data-confirm-label="Ya, tolak">@csrf<button class="btn-ghost-warning btn-sm">Tolak</button></form>
                                    @endif
                                @endcan
                                @can('delete', $item)
                                    <form method="POST" action="{{ route('pengeluaran.destroy', $item) }}" data-confirm="Pengeluaran {{ format_rupiah($item->total_nominal) }} akan dihapus permanen dan tercatat di audit log."
                                          data-confirm-title="Hapus pengeluaran?"
                                          data-confirm-severity="danger"
                                          data-confirm-label="Ya, hapus">@csrf @method('DELETE')<button class="btn-ghost-danger btn-sm">Hapus</button></form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $boleh_hapus ? 9 : 8 }}" class="py-10 text-center text-slate-500">Belum ada pengeluaran pada filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
@endsection
