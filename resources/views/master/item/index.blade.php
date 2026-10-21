@extends('layouts.app')

@section('title', 'Item')
@section('subtitle', 'Bahan, perlengkapan, dan barang yang dibeli')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Harga terakhir otomatis diperbarui dari detail pengeluaran.</p>
        <a href="{{ route('master.item.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Item baru</a>
    </div>

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
                    <tr><td colspan="{{ $bolehHapus ? 7 : 6 }}" class="py-8 text-center text-slate-500">Belum ada item.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
