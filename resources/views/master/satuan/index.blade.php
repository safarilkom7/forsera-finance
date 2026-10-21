@extends('layouts.app')

@section('title', 'Satuan')
@section('subtitle', 'Satuan default untuk item dan detail pengeluaran')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Gunakan singkatan yang konsisten agar laporan mudah dibaca.</p>
        <a href="{{ route('master.satuan.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Satuan baru</a>
    </div>

    <div class="card overflow-hidden" data-pilih-scope>
        @if ($bolehHapus)
            <x-aksi-massal :aksi="route('master.hapus-massal', 'satuan')" kata="satuan" judul="Hapus satuan terpilih?" />
        @endif

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    @if ($bolehHapus)
                        <th class="w-10">
                            <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                <input type="checkbox" data-pilih-semua class="checkbox" aria-label="Pilih semua satuan">
                            </label>
                        </th>
                    @endif
                    <th>Nama</th>
                    <th>Singkatan</th>
                    <th class="text-right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $satuan)
                    @php $alasan = $keterangan[$satuan->id] ?? ''; @endphp
                    <tr>
                        @if ($bolehHapus)
                            <td>
                                @if ($alasan === '')
                                    <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                        <input type="checkbox" form="aksi-massal-form" name="id[]" value="{{ $satuan->id }}"
                                               data-pilih class="checkbox" aria-label="Pilih {{ $satuan->nama }}">
                                    </label>
                                @else
                                    <span aria-hidden="true">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $satuan->nama }}</td>
                        <td><span class="badge badge-gray">{{ $satuan->singkatan }}</span></td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('master.satuan.edit', $satuan) }}" class="btn-ghost btn-sm">Ubah</a>
                                @if ($bolehHapus)
                                    @if ($alasan === '')
                                        <form method="POST" action="{{ route('master.hapus', ['jenis' => 'satuan', 'id' => $satuan->id]) }}"
                                              data-confirm="Satuan {{ $satuan->nama }} akan dihapus permanen dan tercatat di audit log."
                                              data-confirm-title="Hapus satuan?"
                                              data-confirm-severity="danger"
                                              data-confirm-label="Ya, hapus">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-ghost-danger btn-sm">Hapus</button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400" title="Pindahkan dulu item yang memakainya">{{ $alasan }}</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $bolehHapus ? 4 : 3 }}" class="py-8 text-center text-slate-500">Belum ada satuan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
