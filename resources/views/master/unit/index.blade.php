@extends('layouts.app')

@section('title', 'Unit Usaha')
@section('subtitle', 'Tiga unit bisnis '.$branding['brand'])

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Menonaktifkan unit lebih aman daripada menghapus data historis.</p>
        <a href="{{ route('master.unit.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Unit baru</a>
    </div>

    <div class="card overflow-hidden" data-pilih-scope>
        @if ($bolehHapus)
            <x-aksi-massal :aksi="route('master.hapus-massal', 'unit')" kata="unit usaha" judul="Hapus unit usaha terpilih?" />
        @endif

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    @if ($bolehHapus)
                        <th class="w-10">
                            <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                <input type="checkbox" data-pilih-semua class="checkbox" aria-label="Pilih semua unit usaha">
                            </label>
                        </th>
                    @endif
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th class="text-right">Pemasukan</th>
                    <th class="text-right">Pengeluaran</th>
                    <th class="text-right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $unit)
                    @php $alasan = $keterangan[$unit->id] ?? ''; @endphp
                    <tr>
                        @if ($bolehHapus)
                            <td>
                                @if ($alasan === '')
                                    <label class="inline-flex min-h-6 min-w-6 items-center justify-center">
                                        <input type="checkbox" form="aksi-massal-form" name="id[]" value="{{ $unit->id }}"
                                               data-pilih class="checkbox" aria-label="Pilih {{ $unit->nama }}">
                                    </label>
                                @else
                                    <span aria-hidden="true">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="font-mono text-xs font-semibold">{{ $unit->kode }}</td>
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $unit->nama }}</td>
                        <td>{{ $unit->tipe?->getLabel() }}</td>
                        <td><span class="badge {{ $unit->aktif ? 'badge-green' : 'badge-gray' }}">{{ $unit->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="text-right">{{ $unit->pemasukan_count }}</td>
                        <td class="text-right">{{ $unit->pengeluaran_count }}</td>
                        <td class="whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('master.unit.edit', $unit) }}" class="btn-ghost btn-sm">Ubah</a>
                                @if ($bolehHapus)
                                    @if ($alasan === '')
                                        <form method="POST" action="{{ route('master.hapus', ['jenis' => 'unit', 'id' => $unit->id]) }}"
                                              data-confirm="Unit usaha {{ $unit->nama }} akan dihapus permanen dan tercatat di audit log."
                                              data-confirm-title="Hapus unit usaha?"
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
                    <tr><td colspan="{{ $bolehHapus ? 8 : 7 }}" class="py-8 text-center text-slate-500">Belum ada unit usaha.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
