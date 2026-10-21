@extends('layouts.app')

@section('title', 'Tutup Periode')
@section('subtitle', 'Kunci data per periode akuntansi agar laporan tidak berubah')

@section('content')
    <div class="grid gap-5 xl:grid-cols-3">
        <form method="POST" action="{{ route('periode.store') }}" class="card card-pad space-y-4 xl:col-span-1">
            @csrf
            <div>
                <h2 class="section-title">Tutup periode</h2>
                <p class="hint mt-1">Transaksi, rekonsiliasi, dan koreksi pada periode tertutup ditolak untuk semua peran.</p>
                <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                    Periode akuntansi sekarang: <strong>{{ $sekarang['label'] }}</strong> ({{ $sekarang['rentang'] }}).
                    Tanggal mulai periode diatur di menu Pengaturan.
                </p>
            </div>
            <div>
                <label for="periode" class="label">Periode</label>
                <select id="periode" name="periode" class="input">
                    @foreach ($opsi as $pilihan)
                        <option value="{{ $pilihan['kunci'] }}" @selected(old('periode', sprintf('%04d-%02d', $sekarang['tahun'], $sekarang['bulan'])) === $pilihan['kunci'])>
                            {{ $pilihan['label'] }} · {{ $pilihan['rentang'] }}{{ $pilihan['berjalan'] ? ' (berjalan)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('periode')<p class="error-text mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="unit_usaha_id" class="label">Cakupan</label>
                <select id="unit_usaha_id" name="unit_usaha_id" class="input">
                    <option value="">Semua unit</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((int) old('unit_usaha_id') === $unit->id)>{{ $unit->nama }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary" data-confirm-form
                    data-confirm="Transaksi, rekonsiliasi, dan koreksi pada periode yang dipilih akan dikunci untuk semua peran. Tindakan ini tercatat di audit log."
                    data-confirm-title="Tutup periode?"
                    data-confirm-severity="warning"
                    data-confirm-label="Ya, tutup periode">Tutup periode</button>
        </form>

        <div class="card overflow-hidden xl:col-span-2">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <h2 class="section-title">Riwayat penutupan</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Periode</th><th>Unit</th><th>Ditutup oleh</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($data as $periode)
                        <tr>
                            <td class="font-medium">{{ \App\Support\Format::periode($periode->tahun, $periode->bulan) }}<br><span class="text-[11px] text-slate-400">{{ $periode->rentang() }}</span></td>
                            <td>{{ $periode->unitUsaha?->nama ?? 'Semua unit' }}</td>
                            <td class="text-xs">{{ $periode->ditutupOleh?->name }}<br><span class="text-slate-400">{{ format_tanggal($periode->ditutup_at) }}</span></td>
                            <td>
                                @if ($periode->dibuka_kembali_at)
                                    <span class="badge badge-gray">Dibuka kembali</span>
                                    <p class="mt-1 text-[11px] text-slate-500">oleh {{ $periode->dibukaKembaliOleh?->name }}</p>
                                @else
                                    <span class="badge badge-red">Tertutup</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if (! $periode->dibuka_kembali_at)
                                    <button type="button" class="btn-ghost-warning btn-sm" data-dialog-open="buka-{{ $periode->id }}">Buka kembali</button>
                                    <dialog id="buka-{{ $periode->id }}" class="w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-[2px] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        <form method="POST" action="{{ route('periode.buka', $periode) }}">
                                            @csrf
                                            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5 dark:border-slate-800">
                                                <div class="flex items-center gap-2.5">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30">
                                                        <x-icon name="warning" class="h-4 w-4" />
                                                    </span>
                                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Buka kembali periode?</h3>
                                                </div>
                                                <button type="button" data-dialog-close aria-label="Tutup dialog" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                                                    <x-icon name="x" class="h-4 w-4" />
                                                </button>
                                            </div>
                                            <div class="px-5 py-5">
                                                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ \App\Support\Format::periode($periode->tahun, $periode->bulan) }} ({{ $periode->rentang() }}) · {{ $periode->unitUsaha?->nama ?? 'Semua unit' }}. Alasan wajib dicatat di audit log.</p>
                                                <label for="alasan-buka-{{ $periode->id }}" class="label mt-4">Alasan pembukaan <span class="text-red-500">*</span></label>
                                                <textarea id="alasan-buka-{{ $periode->id }}" name="alasan" rows="3" required minlength="5" class="input" placeholder="Contoh: koreksi data dari franchisor"></textarea>
                                            </div>
                                            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3.5 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900/40">
                                                <button type="button" class="btn-secondary" data-dialog-close>Batal</button>
                                                <button type="submit" class="btn-primary">Buka periode</button>
                                            </div>
                                        </form>
                                    </dialog>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-500">Belum ada periode ditutup.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $data->links() }}</div>
        </div>
    </div>
@endsection
