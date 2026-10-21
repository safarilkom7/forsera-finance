@extends('layouts.app')

@section('title', 'Rekonsiliasi '.$rekonsiliasi->kode)
@section('subtitle', $rekonsiliasi->unitUsaha?->nama.' · '.$rekonsiliasi->metode?->nama)

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('rekonsiliasi.index') }}" class="btn-ghost btn-sm"><x-icon name="arrow-left" class="h-4 w-4" /> Kembali</a>
        <div class="flex flex-wrap items-center gap-3">
            <span class="flex items-center gap-2">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</span>
                <x-status-badge :status="$rekonsiliasi->status" />
            </span>
            @if ($rekonsiliasi->status !== \App\Enums\StatusRekonsiliasi::DIBATALKAN && auth()->user()->isOwner())
                <button type="button" class="btn-danger-outline btn-sm" data-dialog-open="dialog-batal">Batalkan</button>
            @endif
        </div>
    </div>

    @if ($rekonsiliasi->status === \App\Enums\StatusRekonsiliasi::DIBATALKAN)
        <div class="mb-5 rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="font-semibold">Rekonsiliasi dibatalkan oleh {{ $rekonsiliasi->dibatalkanOleh?->name }} pada {{ format_tanggal($rekonsiliasi->dibatalkan_at) }}</p>
            <p class="mt-1 text-slate-600 dark:text-slate-300">Alasan: {{ $rekonsiliasi->alasan_pembatalan }}</p>
        </div>
    @endif

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="card card-pad xl:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Unit / Metode</p>
                    <p class="mt-1 font-semibold">{{ $rekonsiliasi->unitUsaha?->nama }}</p>
                    <p class="text-sm text-slate-500">{{ $rekonsiliasi->metode?->nama }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Periode</p>
                    <p class="mt-1 font-semibold">{{ format_tanggal($rekonsiliasi->periode_mulai) }} – {{ format_tanggal($rekonsiliasi->periode_selesai) }}</p>
                    <p class="text-sm text-slate-500">Dana diterima {{ format_tanggal($rekonsiliasi->tanggal_diterima) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Diverifikasi oleh</p>
                    <p class="mt-1 font-semibold">{{ $rekonsiliasi->diverifikasiOleh?->name }}</p>
                    <p class="text-sm text-slate-500">{{ format_tanggal($rekonsiliasi->diverifikasi_at) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jumlah pemasukan</p>
                    <p class="mt-1 font-semibold">{{ $rekonsiliasi->pemasukan->count() }} transaksi</p>
                    <p class="text-sm text-slate-500">{{ $rekonsiliasi->potonganPengeluaran->count() }} pengeluaran kas dipotong</p>
                </div>
            </div>

            @if ($rekonsiliasi->catatan)
                <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-800/60">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan</p>
                    <p class="mt-1 whitespace-pre-line">{{ $rekonsiliasi->catatan }}</p>
                </div>
            @endif

            @if (! empty($rekonsiliasi->bukti))
                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bukti penerimaan</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($rekonsiliasi->bukti as $index => $file)
                            <a href="{{ route('lampiran.download', ['jenis' => 'rekonsiliasi', 'id' => $rekonsiliasi->id, 'index' => $index]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-slate-50 dark:border-slate-700 dark:text-emerald-400">
                                <x-icon name="file-text" class="h-3.5 w-3.5" /> {{ $file['nama_asli'] ?? 'Bukti' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="card card-pad">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Perhitungan selisih</p>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-2"><dt class="text-slate-500">Total dicatat</dt><dd class="font-semibold">{{ format_rupiah($rekonsiliasi->total_dicatat) }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">Potongan kas</dt><dd class="font-semibold">− {{ format_rupiah($rekonsiliasi->potongan_kas) }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">Biaya admin</dt><dd class="font-semibold">− {{ format_rupiah($rekonsiliasi->biaya_admin) }}</dd></div>
                <div class="flex justify-between gap-2 border-t border-slate-200 pt-3 dark:border-slate-700"><dt class="font-semibold">Dana diterima</dt><dd class="font-semibold">{{ format_rupiah($rekonsiliasi->total_diterima) }}</dd></div>
                <div class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
                    <dt class="font-semibold">Selisih</dt>
                    <dd class="text-lg font-bold {{ $rekonsiliasi->selisih < 0 ? 'text-red-600' : ($rekonsiliasi->selisih > 0 ? 'text-orange-600' : 'text-emerald-600') }}">
                        {{ \App\Support\Format::rupiahSigned($rekonsiliasi->selisih) }}
                    </dd>
                </div>
            </dl>
            @if ($rekonsiliasi->arah())
                <p class="mt-2 text-xs text-slate-500">Arah: {{ $rekonsiliasi->arah()->getLabel() }} {{ $rekonsiliasi->arah()->value === 'KURANG' ? '(uang masuk lebih kecil dari catatan)' : '(uang masuk lebih besar dari catatan)' }}</p>
            @endif
            <p class="mt-3 text-[11px] text-slate-400">Rumus: selisih = total diterima − (total dicatat − biaya admin − potongan kas).</p>
        </div>
    </div>

    <div class="mt-5 card overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800"><h2 class="section-title">Pemasukan terkait</h2></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Tanggal</th><th>Kategori</th><th>Catatan</th><th>Dibuat oleh</th><th>Status</th><th class="text-right">Nominal</th></tr></thead>
                <tbody>
                @forelse ($rekonsiliasi->pemasukan as $pemasukan)
                    <tr>
                        <td class="whitespace-nowrap">{{ format_tanggal($pemasukan->tanggal) }}</td>
                        <td>{{ $pemasukan->kategori?->nama }}</td>
                        <td class="max-w-xs truncate">{{ $pemasukan->note ?: '—' }}</td>
                        <td class="text-xs">{{ $pemasukan->dibuatOleh?->name }}</td>
                        <td><x-status-badge :status="$pemasukan->status_verifikasi" /></td>
                        <td class="text-right font-semibold">{{ format_rupiah($pemasukan->nominal) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">Tidak ada pemasukan terkait.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($rekonsiliasi->potonganPengeluaran->isNotEmpty())
        <div class="mt-5 card overflow-hidden">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800"><h2 class="section-title">Pengeluaran kas yang dipotong</h2></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Jenis</th><th>Catatan</th><th class="text-right">Nominal</th></tr></thead>
                    <tbody>
                    @foreach ($rekonsiliasi->potonganPengeluaran as $pengeluaran)
                        <tr>
                            <td>{{ format_tanggal($pengeluaran->tanggal) }}</td>
                            <td>{{ $pengeluaran->jenis?->nama }}</td>
                            <td class="max-w-xs truncate">{{ $pengeluaran->note ?: '—' }}</td>
                            <td class="text-right font-semibold">{{ format_rupiah($pengeluaran->total_nominal) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-5 card">
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800"><h2 class="section-title">Insiden terkait</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($rekonsiliasi->insiden as $insiden)
                <a href="{{ route('insiden.show', $insiden) }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <div>
                        <p class="font-semibold">{{ $insiden->kode }} · {{ $insiden->jenis?->getLabel() }}</p>
                        <p class="text-xs text-slate-500">Arah {{ $insiden->arah?->getLabel() }} · terselesaikan {{ format_rupiah($insiden->nominal_terselesaikan) }} dari {{ format_rupiah($insiden->nominal_selisih) }}</p>
                    </div>
                    <x-status-badge :status="$insiden->status" />
                </a>
            @empty
                <p class="px-4 py-6 text-sm text-slate-500">Tidak ada insiden. Rekonsiliasi ini tidak memiliki selisih di atas toleransi.</p>
            @endforelse
        </div>
    </div>

    <dialog id="dialog-batal" class="w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-[2px] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
        <form method="POST" action="{{ route('rekonsiliasi.batal', $rekonsiliasi) }}">
            @csrf
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/30">
                        <x-icon name="alert-circle" class="h-4 w-4" />
                    </span>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Batalkan rekonsiliasi?</h2>
                </div>
                <button type="button" data-dialog-close aria-label="Tutup dialog" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>
            <div class="px-5 py-5">
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">Pemasukan kembali ke Belum Dicek, pengeluaran kas dilepas, dan insiden terkait dibatalkan. Tindakan ini tercatat di audit log.</p>
                <label for="alasan_batal" class="label mt-4">Alasan pembatalan <span class="text-red-500">*</span></label>
                <textarea id="alasan_batal" name="alasan" rows="3" required minlength="5" class="input" placeholder="Contoh: salah memilih batch QRIS"></textarea>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3.5 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900/40">
                <button type="button" class="btn-secondary" data-dialog-close>Batal</button>
                <button type="submit" class="btn-danger">Ya, batalkan</button>
            </div>
        </form>
    </dialog>
@endsection
