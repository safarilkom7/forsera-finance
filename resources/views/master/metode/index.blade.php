@extends('layouts.app')

@section('title', 'Metode Pembayaran')
@section('subtitle', 'Tunai, QRIS, dan transfer bank')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Tipe metode menentukan perilaku rekonsiliasi (kas tunai vs settlement non-tunai).</p>
        <a href="{{ route('master.metode.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Metode baru</a>
    </div>

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
                    <tr><td colspan="{{ $bolehHapus ? 5 : 4 }}" class="py-8 text-center text-slate-500">Belum ada metode.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
