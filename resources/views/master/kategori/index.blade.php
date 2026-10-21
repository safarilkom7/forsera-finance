@extends('layouts.app')

@section('title', 'Kategori Pemasukan')
@section('subtitle', 'Kategori per unit atau untuk semua unit')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Kategori global (tanpa unit) bisa dipakai di semua unit. Contoh: Pendapatan Lain-lain.</p>
        <a href="{{ route('master.kategori.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Kategori baru</a>
    </div>

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
                    <tr><td colspan="{{ $bolehHapus ? 5 : 4 }}" class="py-8 text-center text-slate-500">Belum ada kategori.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
