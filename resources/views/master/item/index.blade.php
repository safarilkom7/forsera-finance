@extends('layouts.app')

@section('title', 'Item')
@section('subtitle', 'Bahan, perlengkapan, dan barang yang dibeli')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <p class="muted">Harga terakhir otomatis diperbarui dari detail pengeluaran.</p>
            @if ($filter !== [])
                <a href="{{ route('master.item.index') }}" class="btn-ghost btn-sm">
                    <x-icon name="refresh" class="h-3.5 w-3.5" /> Reset filter
                </a>
            @endif
        </div>
        <a href="{{ route('master.item.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Item baru</a>
    </div>

    <form method="GET" action="{{ route('master.item.index') }}" id="filter-item" class="hidden" data-filter-form>
        <button type="submit" tabindex="-1" aria-hidden="true">Terapkan</button>
    </form>

    <div class="card overflow-hidden" data-pilih-scope>
        @if ($bolehHapus)
            <x-aksi-massal :aksi="route('master.hapus-massal', 'item')" kata="item" judul="Hapus item terpilih?" />
        @endif

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    @if ($bolehHapus)
                        <th class="w-10">
                            <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                <input type="checkbox" data-pilih-semua class="checkbox" aria-label="Pilih semua item">
                            </label>
                        </th>
                    @endif
                    <th>Nama</th>
                    <th>Satuan default</th>
                    <th>Jenis</th>
                    <th class="text-right">Harga terakhir</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
                <tr class="filter-baris">
                    @if ($bolehHapus)
                        <x-filter-kosong />
                    @endif
                    <x-filter-teks name="f_nama" :value="$filter['f_nama'] ?? ''" placeholder="Cari nama" form="filter-item" />
                    <x-filter-pilih name="f_satuan" :value="$filter['f_satuan'] ?? ''"
                                    :options="$satuans->mapWithKeys(fn ($satuan) => [$satuan->id => $satuan->singkatan.' — '.$satuan->nama])->all()"
                                    placeholder="Semua satuan" form="filter-item" />
                    <x-filter-pilih name="f_jenis" :value="$filter['f_jenis'] ?? ''"
                                    :options="$jenises->mapWithKeys(fn ($jenis) => [$jenis->id => $jenis->nama])->prepend('Tanpa jenis', 'tanpa')->all()"
                                    placeholder="Semua jenis" form="filter-item" />
                    <x-filter-pilih name="f_harga" :value="$filter['f_harga'] ?? ''"
                                    :options="['ada' => 'Sudah ada harga', 'kosong' => 'Belum ada harga']"
                                    placeholder="Semua harga" form="filter-item" />
                    <x-filter-pilih name="f_status" :value="$filter['f_status'] ?? ''" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" placeholder="Semua status" form="filter-item" />
                    <x-filter-kosong />
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $item)
                    @php $alasan = $keterangan[$item->id] ?? ''; @endphp
                    <tr>
                        @if ($bolehHapus)
                            <td>
                                @if ($alasan === '')
                                    <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                        <input type="checkbox" form="aksi-massal-form" name="id[]" value="{{ $item->id }}"
                                               data-pilih class="checkbox" aria-label="Pilih {{ $item->nama }}">
                                    </label>
                                @else
                                    <span aria-hidden="true">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $item->nama }}</td>
                        <td>{{ $item->satuanDefault?->singkatan }} <span class="text-xs text-slate-500 dark:text-slate-400">({{ $item->satuanDefault?->nama }})</span></td>
                        <td>{{ $item->jenisPengeluaran?->nama ?? '—' }}</td>
                        <td class="text-right">{{ $item->harga_terakhir ? format_rupiah($item->harga_terakhir) : '—' }}</td>
                        <td><span class="badge {{ $item->aktif ? 'badge-green' : 'badge-gray' }}">{{ $item->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('master.item.edit', $item) }}" class="btn-ghost btn-sm">Ubah</a>
                                @if ($bolehHapus)
                                    @if ($alasan === '')
                                        <form method="POST" action="{{ route('master.hapus', ['jenis' => 'item', 'id' => $item->id]) }}"
                                              data-confirm="Item {{ $item->nama }} akan dihapus permanen dan tercatat di audit log."
                                              data-confirm-title="Hapus item?"
                                              data-confirm-severity="danger"
                                              data-confirm-label="Ya, hapus">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-ghost-danger btn-sm">Hapus</button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400" title="Nonaktifkan saja agar riwayat pembelian tetap utuh">{{ $alasan }}</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $bolehHapus ? 7 : 6 }}" class="py-8 text-center text-slate-500">
                            @if ($filter !== [])
                                Tidak ada item yang cocok. <a href="{{ route('master.item.index') }}" class="font-semibold text-emerald-700 underline dark:text-emerald-400">Reset filter</a>.
                            @else
                                Belum ada item.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
