@extends('layouts.app')

@section('title', 'Jenis Pengeluaran')
@section('subtitle', 'Bahan baku, gaji, sewa, royalti, dan lainnya')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <p class="muted">Nonaktifkan jenis yang tidak dipakai; data lama tetap tersimpan.</p>
            @if ($filter !== [])
                <a href="{{ route('master.jenis.index') }}" class="btn-ghost btn-sm">
                    <x-icon name="refresh" class="h-3.5 w-3.5" /> Reset filter
                </a>
            @endif
        </div>
        <a href="{{ route('master.jenis.create') }}" class="btn-primary shrink-0 whitespace-nowrap"><x-icon name="plus" class="h-4 w-4" /> Jenis baru</a>
    </div>

    <form method="GET" action="{{ route('master.jenis.index') }}" id="filter-jenis" class="hidden" data-filter-form>
        <button type="submit" tabindex="-1" aria-hidden="true">Terapkan</button>
    </form>

    <div class="card overflow-hidden" data-pilih-scope>
        @if ($bolehHapus)
            <x-aksi-massal :aksi="route('master.hapus-massal', 'jenis')" kata="jenis pengeluaran" judul="Hapus jenis pengeluaran terpilih?" />
        @endif

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    @if ($bolehHapus)
                        <th class="w-10">
                            <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                <input type="checkbox" data-pilih-semua class="checkbox" aria-label="Pilih semua jenis pengeluaran">
                            </label>
                        </th>
                    @endif
                    <th>Nama</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
                <tr class="filter-baris">
                    @if ($bolehHapus)
                        <x-filter-kosong />
                    @endif
                    <x-filter-teks name="f_nama" :value="$filter['f_nama'] ?? ''" placeholder="Cari nama" form="filter-jenis" />
                    <x-filter-pilih name="f_status" :value="$filter['f_status'] ?? ''" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" placeholder="Semua status" form="filter-jenis" />
                    <x-filter-kosong />
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $jenis)
                    @php $alasan = $keterangan[$jenis->id] ?? ''; @endphp
                    <tr>
                        @if ($bolehHapus)
                            <td>
                                @if ($alasan === '')
                                    <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                        <input type="checkbox" form="aksi-massal-form" name="id[]" value="{{ $jenis->id }}"
                                               data-pilih class="checkbox" aria-label="Pilih {{ $jenis->nama }}">
                                    </label>
                                @else
                                    <span aria-hidden="true">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $jenis->nama }}</td>
                        <td><span class="badge {{ $jenis->aktif ? 'badge-green' : 'badge-gray' }}">{{ $jenis->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('master.jenis.edit', $jenis) }}" class="btn-ghost btn-sm">Ubah</a>
                                @if ($bolehHapus)
                                    @if ($alasan === '')
                                        <form method="POST" action="{{ route('master.hapus', ['jenis' => 'jenis', 'id' => $jenis->id]) }}"
                                              data-confirm="Jenis pengeluaran {{ $jenis->nama }} akan dihapus permanen dan tercatat di audit log."
                                              data-confirm-title="Hapus jenis pengeluaran?"
                                              data-confirm-severity="danger"
                                              data-confirm-label="Ya, hapus">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-ghost-danger btn-sm">Hapus</button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400" title="Nonaktifkan saja agar riwayat tetap utuh">{{ $alasan }}</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $bolehHapus ? 4 : 3 }}" class="py-8 text-center text-slate-500">
                            @if ($filter !== [])
                                Tidak ada jenis pengeluaran yang cocok. <a href="{{ route('master.jenis.index') }}" class="font-semibold text-emerald-700 underline dark:text-emerald-400">Reset filter</a>.
                            @else
                                Belum ada jenis pengeluaran.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
