@extends('layouts.app')

@section('title', 'Kategori Pemasukan')
@section('subtitle', 'Kategori per unit atau untuk semua unit')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <p class="muted">Kategori global (tanpa unit) bisa dipakai di semua unit. Contoh: Pendapatan Lain-lain.</p>
            @if ($filter !== [])
                <a href="{{ route('master.kategori.index') }}" class="btn-ghost btn-sm">
                    <x-icon name="refresh" class="h-3.5 w-3.5" /> Reset filter
                </a>
            @endif
        </div>
        <a href="{{ route('master.kategori.create') }}" class="btn-primary shrink-0 whitespace-nowrap"><x-icon name="plus" class="h-4 w-4" /> Kategori baru</a>
    </div>

    <form method="GET" action="{{ route('master.kategori.index') }}" id="filter-kategori" class="hidden" data-filter-form>
        <button type="submit" tabindex="-1" aria-hidden="true">Terapkan</button>
    </form>

    <div class="card overflow-hidden" data-pilih-scope>
        @if ($bolehHapus)
            <x-aksi-massal :aksi="route('master.hapus-massal', 'kategori')" kata="kategori" judul="Hapus kategori pemasukan terpilih?" />
        @endif

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    @if ($bolehHapus)
                        <th class="w-10">
                            <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                <input type="checkbox" data-pilih-semua class="checkbox" aria-label="Pilih semua kategori pemasukan">
                            </label>
                        </th>
                    @endif
                    <th>Nama</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
                <tr class="filter-baris">
                    @if ($bolehHapus)
                        <x-filter-kosong />
                    @endif
                    <x-filter-teks name="f_nama" :value="$filter['f_nama'] ?? ''" placeholder="Cari nama" form="filter-kategori" />
                    <x-filter-pilih name="f_unit" :value="$filter['f_unit'] ?? ''"
                                    :options="collect($units)->mapWithKeys(fn ($unit) => [$unit->id => $unit->nama])->prepend('Tanpa unit (global)', 'tanpa')->all()"
                                    placeholder="Semua unit" form="filter-kategori" />
                    <x-filter-pilih name="f_status" :value="$filter['f_status'] ?? ''" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']" placeholder="Semua status" form="filter-kategori" />
                    <x-filter-kosong />
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $kategori)
                    @php $alasan = $keterangan[$kategori->id] ?? ''; @endphp
                    <tr>
                        @if ($bolehHapus)
                            <td>
                                @if ($alasan === '')
                                    <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                        <input type="checkbox" form="aksi-massal-form" name="id[]" value="{{ $kategori->id }}"
                                               data-pilih class="checkbox" aria-label="Pilih {{ $kategori->nama }}">
                                    </label>
                                @else
                                    <span aria-hidden="true">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $kategori->nama }}</td>
                        <td>{{ $kategori->unitUsaha?->nama ?? 'Semua unit' }}</td>
                        <td><span class="badge {{ $kategori->aktif ? 'badge-green' : 'badge-gray' }}">{{ $kategori->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('master.kategori.edit', $kategori) }}" class="btn-ghost btn-sm">Ubah</a>
                                @if ($bolehHapus)
                                    @if ($alasan === '')
                                        <form method="POST" action="{{ route('master.hapus', ['jenis' => 'kategori', 'id' => $kategori->id]) }}"
                                              data-confirm="Kategori pemasukan {{ $kategori->nama }} akan dihapus permanen dan tercatat di audit log."
                                              data-confirm-title="Hapus kategori pemasukan?"
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
                                Tidak ada kategori yang cocok. <a href="{{ route('master.kategori.index') }}" class="font-semibold text-emerald-700 underline dark:text-emerald-400">Reset filter</a>.
                            @else
                                Belum ada kategori.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
