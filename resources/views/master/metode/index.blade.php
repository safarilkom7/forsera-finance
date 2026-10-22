@extends('layouts.app')

@section('title', 'Metode Pembayaran')
@section('subtitle', 'Tunai, QRIS, dan transfer bank')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <p class="muted">Tipe metode menentukan perilaku rekonsiliasi (kas tunai vs settlement non-tunai).</p>
            @if ($filter !== [])
                <a href="{{ route('master.metode.index') }}" class="btn-ghost btn-sm">
                    <x-icon name="refresh" class="h-3.5 w-3.5" /> Reset filter
                </a>
            @endif
        </div>
        <a href="{{ route('master.metode.create') }}" class="btn-primary shrink-0 whitespace-nowrap"><x-icon name="plus" class="h-4 w-4" /> Metode baru</a>
    </div>

    <form method="GET" action="{{ route('master.metode.index') }}" id="filter-metode" class="hidden" data-filter-form>
        <button type="submit" tabindex="-1" aria-hidden="true">Terapkan</button>
    </form>

    <div class="card overflow-hidden" data-pilih-scope>
        @if ($bolehHapus)
            <x-aksi-massal :aksi="route('master.hapus-massal', 'metode')" kata="metode" judul="Hapus metode pembayaran terpilih?" />
        @endif

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    @if ($bolehHapus)
                        <th class="w-10">
                            <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                <input type="checkbox" data-pilih-semua class="checkbox" aria-label="Pilih semua metode pembayaran">
                            </label>
                        </th>
                    @endif
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
                <tr class="filter-baris">
                    @if ($bolehHapus)
                        <x-filter-kosong />
                    @endif
                    <x-filter-teks name="f_nama" :value="$filter['f_nama'] ?? ''" placeholder="Cari nama" form="filter-metode" />
                    <x-filter-pilih name="f_tipe" :value="$filter['f_tipe'] ?? ''" :options="$tipeMetodes" placeholder="Semua tipe" form="filter-metode" />
                    <x-filter-pilih name="f_status" :value="$filter['f_status'] ?? ''" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" placeholder="Semua status" form="filter-metode" />
                    <x-filter-kosong />
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $metode)
                    @php $alasan = $keterangan[$metode->id] ?? ''; @endphp
                    <tr>
                        @if ($bolehHapus)
                            <td>
                                @if ($alasan === '')
                                    <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                        <input type="checkbox" form="aksi-massal-form" name="id[]" value="{{ $metode->id }}"
                                               data-pilih class="checkbox" aria-label="Pilih {{ $metode->nama }}">
                                    </label>
                                @else
                                    <span aria-hidden="true">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $metode->nama }}</td>
                        <td>{{ $metode->tipe?->getLabel() }}</td>
                        <td><span class="badge {{ $metode->aktif ? 'badge-green' : 'badge-gray' }}">{{ $metode->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('master.metode.edit', $metode) }}" class="btn-ghost btn-sm">Ubah</a>
                                @if ($bolehHapus)
                                    @if ($alasan === '')
                                        <form method="POST" action="{{ route('master.hapus', ['jenis' => 'metode', 'id' => $metode->id]) }}"
                                              data-confirm="Metode pembayaran {{ $metode->nama }} akan dihapus permanen dan tercatat di audit log."
                                              data-confirm-title="Hapus metode pembayaran?"
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
                        <td colspan="{{ $bolehHapus ? 5 : 4 }}" class="py-8 text-center text-slate-500">
                            @if ($filter !== [])
                                Tidak ada metode yang cocok. <a href="{{ route('master.metode.index') }}" class="font-semibold text-emerald-700 underline dark:text-emerald-400">Reset filter</a>.
                            @else
                                Belum ada metode.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
