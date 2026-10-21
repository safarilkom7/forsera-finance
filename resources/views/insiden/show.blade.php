@extends('layouts.app')

@section('title', 'Insiden '.$insiden->kode)
@section('subtitle', $insiden->rekonsiliasi?->unitUsaha?->nama.' · '.$insiden->jenis?->getLabel())

@section('content')
    @php
        $final = $insiden->status->final();
        $opsiTipe = collect($tipePenyelesaian)->filter(function ($tipe) use ($insiden) {
            if ($tipe === \App\Enums\TipePenyelesaian::WRITE_OFF) {
                return auth()->user()->isOwner();
            }
            if ($tipe === \App\Enums\TipePenyelesaian::CATAT_PENDAPATAN_LAIN) {
                return $insiden->arah === \App\Enums\ArahSelisih::LEBIH;
            }
            return true;
        });
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('insiden.index') }}" class="btn-ghost btn-sm"><x-icon name="arrow-left" class="h-4 w-4" /> Kembali</a>
        <div class="flex items-center gap-2">
            <x-status-badge :status="$insiden->status" />
            <x-status-badge :status="$insiden->arah" />
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <div class="card card-pad">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Selisih</p>
                        <p class="mt-1 text-2xl font-bold {{ $insiden->arah?->value === 'KURANG' ? 'text-red-600' : 'text-orange-600' }}">{{ format_rupiah($insiden->nominal_selisih) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Arah: {{ $insiden->arah?->getLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Terselesaikan</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ format_rupiah($insiden->nominal_terselesaikan) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Sisa {{ format_rupiah($insiden->sisa()) }}</p>
                    </div>
                </div>

                <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-400">Rekonsiliasi</dt><dd><a class="font-semibold text-emerald-600 hover:underline dark:text-emerald-400" href="{{ route('rekonsiliasi.show', $insiden->rekonsiliasi) }}">{{ $insiden->rekonsiliasi?->kode }}</a></dd></div>
                    <div><dt class="text-xs text-slate-400">Periode rekonsiliasi</dt><dd>{{ format_tanggal($insiden->rekonsiliasi?->periode_mulai) }} – {{ format_tanggal($insiden->rekonsiliasi?->periode_selesai) }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Dibuat oleh</dt><dd>{{ $insiden->dibuatOleh?->name }} · {{ format_tanggal($insiden->created_at) }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Penanggung jawab</dt><dd>{{ $insiden->penanggungJawab?->name ?? 'Belum ditugaskan' }}</dd></div>
                    @if ($insiden->ditutup_at)
                        <div><dt class="text-xs text-slate-400">Ditutup oleh</dt><dd>{{ $insiden->ditutupOleh?->name }} · {{ format_tanggal($insiden->ditutup_at) }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Tipe penyelesaian</dt><dd>{{ $insiden->tipe_penyelesaian?->getLabel() ?? '—' }}</dd></div>
                    @endif
                </dl>

                @if ($insiden->penyebab)
                    <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-800/60">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Penyebab</p>
                        <p class="mt-1 whitespace-pre-line">{{ $insiden->penyebab }}</p>
                    </div>
                @endif
                @if ($insiden->catatan_penyelesaian)
                    <div class="mt-3 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Catatan penyelesaian</p>
                        <p class="mt-1 whitespace-pre-line">{{ $insiden->catatan_penyelesaian }}</p>
                    </div>
                @endif

                @if (! empty($insiden->lampiran))
                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bukti</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($insiden->lampiran as $index => $file)
                                <a href="{{ route('lampiran.download', ['jenis' => 'insiden', 'id' => $insiden->id, 'index' => $index]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-slate-50 dark:border-slate-700 dark:text-emerald-400">
                                    <x-icon name="file-text" class="h-3.5 w-3.5" /> {{ $file['nama_asli'] ?? 'Bukti' }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @unless ($final)
                <div class="card card-pad">
                    <h2 class="section-title">Tindak lanjut</h2>
                    <p class="hint mt-1">Pilih satu tindakan. Setiap perubahan tercatat pada timeline.</p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($insiden->status === \App\Enums\StatusInsiden::OPEN)
                            <form method="POST" action="{{ route('insiden.investigasi', $insiden) }}" class="flex flex-1 flex-wrap items-end gap-2">
                                @csrf
                                <div class="min-w-[12rem] flex-1">
                                    <label for="catatan_investigasi" class="label">Catatan investigasi (opsional)</label>
                                    <input type="text" id="catatan_investigasi" name="catatan" class="input" maxlength="1000" placeholder="Contoh: cek mutasi rekening">
                                </div>
                                <button class="btn-secondary">Mulai investigasi</button>
                            </form>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('insiden.tugaskan', $insiden) }}" class="mt-4 flex flex-wrap items-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        @csrf
                        <div class="min-w-[14rem] flex-1">
                            <label for="penanggung_jawab_id" class="label">Penanggung jawab</label>
                            <select id="penanggung_jawab_id" name="penanggung_jawab_id" class="input">
                                <option value="">— Tidak ada —</option>
                                @foreach ($pengguna as $orang)
                                    <option value="{{ $orang->id }}" @selected($insiden->penanggung_jawab_id === $orang->id)>{{ $orang->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn-secondary">Simpan PJ</button>
                    </form>
                </div>

                <div class="card card-pad" id="form-penyelesaian">
                    <h2 class="section-title">Selesaikan insiden</h2>
                    <p class="hint mt-1">Penutupan wajib mengisi penyebab, tipe penyelesaian, dan catatan.</p>

                    <form method="POST" action="{{ route('insiden.selesaikan', $insiden) }}" class="mt-4 space-y-4" enctype="multipart/form-data">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="tipe_penyelesaian" class="label">Tipe penyelesaian</label>
                                <select id="tipe_penyelesaian" name="tipe_penyelesaian" required class="input">
                                    <option value="">Pilih tipe</option>
                                    @foreach ($opsiTipe as $tipe)
                                        <option value="{{ $tipe->value }}" @selected(old('tipe_penyelesaian') === $tipe->value)>{{ $tipe->getLabel() }}</option>
                                    @endforeach
                                </select>
                                @error('tipe_penyelesaian')<p class="error-text">{{ $message }}</p>@enderror
                            </div>
                            <div id="nominal-wrapper" class="hidden">
                                <label for="nominal" class="label">Nominal diterima</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-400">Rp</span>
                                    <input type="text" id="nominal" name="nominal" inputmode="numeric" data-rupiah value="{{ old('nominal') }}" class="input pl-10" placeholder="0">
                                </div>
                                <p class="hint">Boleh bertahap. Insiden ditutup bila total ≥ {{ format_rupiah($insiden->nominal_selisih) }}.</p>
                                @error('nominal')<p class="error-text">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label for="penyebab" class="label">Penyebab</label>
                            <textarea id="penyebab" name="penyebab" rows="2" required maxlength="1000" class="input" placeholder="Contoh: kasir lupa mencatat transaksi tunai">{{ old('penyebab', $insiden->penyebab) }}</textarea>
                            @error('penyebab')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="catatan_penyelesaian" class="label">Catatan penyelesaian</label>
                            <textarea id="catatan_penyelesaian" name="catatan_penyelesaian" rows="3" required maxlength="2000" class="input" placeholder="Jelaskan bukti dan tindakan yang dilakukan">{{ old('catatan_penyelesaian') }}</textarea>
                            @error('catatan_penyelesaian')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div id="hint-koreksi" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                            Koreksi hanya bisa ditutup bila selisih rekonsiliasi sudah dalam toleransi {{ format_rupiah((int) app(\App\Services\PengaturanService::class)->getInt('toleransi_selisih', 1000)) }}. Koreksi pemasukan dilakukan Owner dari menu Pemasukan.
                        </div>
                        <div id="hint-writeoff" class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                            Write-off akan menghapus sisa selisih dan tercatat sebagai “Selisih dihapuskan” pada laporan. Hanya Owner.
                        </div>

                        <x-file-upload
                            name="lampiran"
                            label="Bukti penyelesaian"
                            hint="Foto setoran ulang, bukti transfer, atau tangkapan layar mutasi."
                            :bare="true" />

                        <button type="submit" class="btn-primary">Catat penyelesaian</button>
                    </form>
                </div>
            @endunless

            <div class="card">
                <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    <h2 class="section-title">Timeline</h2>
                </div>
                <div class="space-y-0 p-4 sm:p-5">
                    @forelse ($insiden->log as $log)
                        <div class="relative border-l-2 border-slate-200 pb-5 pl-5 last:pb-0 dark:border-slate-700">
                            <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full bg-emerald-500 ring-4 ring-white dark:ring-slate-900"></span>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold">{{ $log->aksi }}</p>
                                <p class="text-xs text-slate-400">{{ format_tanggal($log->created_at).' '.$log->created_at?->format('H:i') }}</p>
                            </div>
                            <p class="text-xs text-slate-500">{{ $log->user?->name ?? 'Sistem' }}@if ($log->nominal) · {{ format_rupiah($log->nominal) }}@endif</p>
                            @if ($log->catatan)
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $log->catatan }}</p>
                            @endif
                            @if (! empty($log->lampiran))
                                <div class="mt-1 flex flex-wrap gap-2">
                                    @foreach ($log->lampiran as $file)
                                        <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500 dark:bg-slate-800"><x-icon name="file-text" class="h-3 w-3" /> {{ $file['nama_asli'] ?? 'Bukti' }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada aktivitas.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card card-pad">
                <h2 class="section-title">Ringkasan</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Jenis</dt><dd class="font-medium">{{ $insiden->jenis?->getLabel() }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Arah</dt><dd class="font-medium">{{ $insiden->arah?->getLabel() }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Status</dt><dd><x-status-badge :status="$insiden->status" /></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Sisa</dt><dd class="font-semibold">{{ format_rupiah($insiden->sisa()) }}</dd></div>
                </dl>
            </div>

            <div class="card card-pad">
                <h2 class="section-title">Rekomendasi</h2>
                <ul class="mt-3 space-y-2 text-xs text-slate-600 dark:text-slate-300">
                    <li>KURANG: cek setoran kasir, mutasi rekening, atau potongan MDR.</li>
                    <li>LEBIH: cek pencatatan ganda atau dana masuk yang belum dicatat.</li>
                    <li>Setoran susulan dan dana cair susulan dapat dicatat bertahap.</li>
                    <li>Owner dapat mengoreksi pemasukan; rekonsiliasi dihitung ulang otomatis.</li>
                </ul>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const tipe = document.getElementById('tipe_penyelesaian');
                const nominal = document.getElementById('nominal-wrapper');
                const hintKoreksi = document.getElementById('hint-koreksi');
                const hintWriteoff = document.getElementById('hint-writeoff');
                if (!tipe) return;

                const sync = () => {
                    const value = tipe.value;
                    const butuhNominal = ['SETORAN_SUSULAN', 'DANA_CAIR_SUSULAN', 'GANTI_RUGI'].includes(value);
                    nominal?.classList.toggle('hidden', !butuhNominal);
                    hintKoreksi?.classList.toggle('hidden', value !== 'KOREKSI_DATA');
                    hintWriteoff?.classList.toggle('hidden', value !== 'WRITE_OFF');
                };

                tipe.addEventListener('change', sync);
                sync();
            });
        </script>
    @endpush
@endsection
