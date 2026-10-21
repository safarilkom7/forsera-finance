@extends('layouts.app')

@section('title', 'Rekonsiliasi Baru')
@section('subtitle', 'Langkah ' . ($langkah === 1 ? '1 dari 3' : '2–3 dari 3'))

@section('content')
    @php
        $isOwner = auth()->user()->isOwner();
        $dipilih = collect(old('pemasukan_ids', $kandidat->reject(fn ($p) => auth()->user()->tundukMakerChecker() && $p->dibuat_oleh === auth()->id())->pluck('id')->all()))->map(fn ($v) => (int) $v)->all();
        $metodeTipe = $metodeTerpilih?->tipe?->value;
        $menungguDefault = (bool) old('menunggu_settlement', false);
        $tanggalDiterimaDefault = old('tanggal_diterima', now()->toDateString());
    @endphp

    <ol class="mb-6 flex items-center gap-2 text-xs font-semibold sm:gap-3">
        <li class="flex items-center gap-2 rounded-full px-3 py-1.5 {{ $langkah === 1 ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' }}">
            <span class="grid h-5 w-5 place-items-center rounded-full bg-white/20">{{ $langkah === 1 ? '1' : '✓' }}</span> Pilih data
        </li>
        <li class="h-px w-4 bg-slate-300 dark:bg-slate-700 sm:w-8"></li>
        <li class="rounded-full px-3 py-1.5 {{ $langkah === 2 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">2. Isi penerimaan</li>
        <li class="h-px w-4 bg-slate-300 dark:bg-slate-700 sm:w-8"></li>
        <li class="rounded-full px-3 py-1.5 bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">3. Tinjau &amp; simpan</li>
    </ol>

    @if ($langkah === 1)
        <form method="GET" action="{{ route('rekonsiliasi.create') }}" class="card card-pad max-w-3xl">
            <h2 class="section-title">Pilih unit, metode, dan rentang tanggal</h2>
            <p class="muted mt-1">Sistem akan menampilkan semua pemasukan yang belum dicek pada rentang tersebut.</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="unit" class="label">Unit usaha</label>
                    <x-pilihan id="unit" name="unit" required class="input">
                        <option value="">Pilih unit</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected((int) $unitId === $unit->id)>{{ $unit->nama }}</option>
                        @endforeach
                    </x-pilihan>
                </div>
                <div>
                    <label for="metode" class="label">Metode pembayaran</label>
                    <x-pilihan id="metode" name="metode" required class="input">
                        <option value="">Pilih metode</option>
                        @foreach ($metodes as $metode)
                            <option value="{{ $metode->id }}" @selected((int) $metodeId === $metode->id)>{{ $metode->nama }}</option>
                        @endforeach
                    </x-pilihan>
                </div>
                <div>
                    <label for="mulai" class="label">Periode mulai</label>
                    <x-tanggal id="mulai" name="mulai" required value="{{ $mulai }}" class="input" />
                </div>
                <div>
                    <label for="selesai" class="label">Periode selesai</label>
                    <x-tanggal id="selesai" name="selesai" required value="{{ $selesai }}" class="input" />
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn-primary">Tampilkan pemasukan <x-icon name="chevron-right" class="h-4 w-4" /></button>
                <a href="{{ route('rekonsiliasi.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    @else
        <div class="mb-5 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900">
            <div>
                <span class="font-semibold">{{ $unitTerpilih?->nama }}</span>
                <span class="text-slate-500 dark:text-slate-400">·</span> {{ $metodeTerpilih?->nama }}
                <span class="text-slate-500 dark:text-slate-400">·</span> {{ format_tanggal($mulai) }} – {{ format_tanggal($selesai) }}
            </div>
            <a href="{{ route('rekonsiliasi.create') }}" class="inline-flex min-h-6 items-center text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Ubah pilihan</a>
        </div>

        @if ($kandidat->isEmpty())
            <div class="card card-pad py-12 text-center">
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-500 dark:text-slate-400 dark:bg-slate-800"><x-icon name="search" class="h-6 w-6" /></div>
                <h2 class="mt-3 text-lg font-semibold">Tidak ada pemasukan yang bisa direkonsiliasi</h2>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">Semua pemasukan pada kombinasi ini mungkin sudah diverifikasi atau belum diinput. Coba rentang tanggal lain.</p>
                <a href="{{ route('rekonsiliasi.create') }}" class="btn-secondary mt-5 inline-flex">Kembali ke langkah 1</a>
            </div>
        @else
            <form method="POST" action="{{ route('rekonsiliasi.store') }}" id="form-rekonsiliasi" class="grid gap-5 xl:grid-cols-3" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="unit_usaha_id" value="{{ $unitId }}">
                <input type="hidden" name="metode_pembayaran_id" value="{{ $metodeId }}">
                <input type="hidden" name="periode_mulai" value="{{ $mulai }}">
                <input type="hidden" name="periode_selesai" value="{{ $selesai }}">

                <div class="space-y-5 xl:col-span-2">
                    <div class="card overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                            <div>
                                <h2 class="section-title">Pemasukan dalam rentang</h2>
                                <p class="hint">{{ $kandidat->count() }} transaksi tersedia</p>
                            </div>
                            <div class="flex gap-2 text-xs">
                                <button type="button" class="btn-ghost btn-sm" id="pilih-semua">Pilih semua</button>
                                <button type="button" class="btn-ghost btn-sm" id="kosongkan">Kosongkan</button>
                            </div>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($kandidat as $pemasukan)
                                @php $milikSendiri = auth()->user()->tundukMakerChecker() && $pemasukan->dibuat_oleh === auth()->id(); @endphp
                                <label class="flex cursor-pointer items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40 {{ $milikSendiri ? 'opacity-60' : '' }}">
                                    <input type="checkbox" name="pemasukan_ids[]" value="{{ $pemasukan->id }}"
                                           class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500"
                                           data-nominal="{{ $pemasukan->nominal }}"
                                           @checked(in_array($pemasukan->id, $dipilih, true))
                                           @disabled($milikSendiri)>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ format_tanggal($pemasukan->tanggal) }} · {{ $pemasukan->kategori?->nama }}</p>
                                            <p class="text-sm font-bold">{{ format_rupiah($pemasukan->nominal) }}</p>
                                        </div>
                                        <p class="text-xs text-slate-500">{{ $pemasukan->note ?: 'Tanpa catatan' }} · oleh {{ $pemasukan->dibuatOleh?->name }}</p>
                                        @if ($milikSendiri)
                                            <p class="mt-1 text-[11px] font-medium text-amber-700 dark:text-amber-400">Dibuat oleh Anda — tidak dapat diverifikasi sendiri (maker-checker).</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
                            <span class="text-sm font-semibold">Total dipilih</span>
                            <span class="text-lg font-bold" id="total-dipilih">Rp 0</span>
                        </div>
                        @error('pemasukan_ids')<p class="error-text px-4 pb-3">{{ $message }}</p>@enderror
                    </div>

                    <div class="card card-pad" id="penerimaan-section">
                        <h2 class="section-title">Dana diterima</h2>
                        <p class="hint mt-1">Masukkan jumlah uang yang benar-benar masuk ke rekening/kas untuk batch ini.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="tanggal_diterima" class="label">Tanggal dana diterima</label>
                                <x-tanggal id="tanggal_diterima" name="tanggal_diterima" value="{{ $tanggalDiterimaDefault }}" class="input" />
                            </div>
                            <div>
                                <label for="total_diterima" class="label">Total diterima</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-500 dark:text-slate-400">Rp</span>
                                    <input type="text" id="total_diterima" name="total_diterima" inputmode="numeric" data-rupiah value="{{ old('total_diterima') }}" class="input pl-10 text-lg font-bold" placeholder="0">
                                </div>
                            </div>
                            <div>
                                <label for="biaya_admin" class="label">Biaya admin / MDR</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-500 dark:text-slate-400">Rp</span>
                                    <input type="text" id="biaya_admin" name="biaya_admin" inputmode="numeric" data-rupiah value="{{ old('biaya_admin') }}" class="input pl-10" placeholder="0">
                                </div>
                                @if ($saran > 0)
                                    <p class="hint">Saran MDR {{ \App\Support\Format::persen($mdr) }}: {{ format_rupiah($saran) }}.
                                        <button type="button" id="isi-saran" class="inline-flex min-h-6 items-center font-semibold text-emerald-700 hover:underline dark:text-emerald-400" data-saran="{{ $saran }}">Isi otomatis</button>
                                    </p>
                                @endif
                            </div>
                            <div>
                                <label for="potongan_kas_manual" class="label">Potongan kas tunai {{ $isOwner ? '(Owner)' : '' }}</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-500 dark:text-slate-400">Rp</span>
                                    <input type="text" id="potongan_kas_manual" name="potongan_kas_manual" inputmode="numeric" data-rupiah value="{{ old('potongan_kas_manual') }}" class="input pl-10" placeholder="{{ number_format($potongan, 0, ',', '.') }}" @readonly(! $isOwner)>
                                </div>
                                <p class="hint">Otomatis dari pengeluaran kas harian: {{ format_rupiah($potongan) }}. {{ $isOwner ? 'Koreksi hanya bila perlu; catatan wajib diisi.' : '' }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="catatan" class="label">Catatan</label>
                            <textarea id="catatan" name="catatan" rows="2" class="input" maxlength="2000" placeholder="Contoh: settlement QRIS tanggal 19, sudah dipotong MDR">{{ old('catatan') }}</textarea>
                        </div>

                        @if ($metodeTipe !== 'TUNAI')
                            <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                                <input type="checkbox" name="menunggu_settlement" value="1" id="menunggu_settlement" class="mt-0.5 h-4 w-4 rounded border-amber-300 text-amber-700" @checked($menungguDefault)>
                                <span>
                                    <span class="font-semibold text-amber-800 dark:text-amber-300">Tandai menunggu settlement</span>
                                    <span class="mt-0.5 block text-xs text-amber-700 dark:text-amber-300/80">Dana belum masuk. Pemasukan berubah menjadi Menunggu Settlement dan tidak membuat insiden.</span>
                                </span>
                            </label>
                        @endif

                        <x-file-upload
                            name="bukti"
                            label="Bukti penerimaan"
                            hint="Slip setoran, mutasi rekening, atau tangkapan layar settlement."
                            :bare="true" />
                    </div>
                </div>

                <div class="xl:col-span-1">
                    <div class="card card-pad xl:sticky xl:top-24">
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Langkah 3</p>
                        <h2 class="section-title mt-1">Ringkasan &amp; simpan</h2>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-500">Total dicatat</dt>
                                <dd class="font-semibold" id="ringkasan-dicatat">Rp 0</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-500">Potongan kas</dt>
                                <dd class="font-semibold" id="ringkasan-potongan">Rp {{ number_format($potongan, 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-500">Biaya admin</dt>
                                <dd class="font-semibold" id="ringkasan-biaya">Rp 0</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-500">Dana diterima</dt>
                                <dd class="font-semibold" id="ringkasan-diterima">Rp 0</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2 border-t border-slate-200 pt-3 dark:border-slate-700">
                                <dt class="font-semibold">Selisih</dt>
                                <dd class="text-lg font-bold" id="ringkasan-selisih">Rp 0</dd>
                            </div>
                        </dl>

                        <div class="mt-3 text-xs" id="ringkasan-status"></div>

                        <button type="submit" class="btn-primary mt-5 w-full" id="tombol-simpan">
                            <x-icon name="check" class="h-4 w-4" /> Simpan rekonsiliasi
                        </button>
                        <p class="mt-2 text-center text-[11px] text-slate-500 dark:text-slate-400">Selisih di atas toleransi akan membuat insiden otomatis.</p>
                        <a href="{{ route('rekonsiliasi.index') }}" class="btn-ghost mt-2 w-full">Batal</a>
                    </div>
                </div>
            </form>
        @endif
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const checkboxes = Array.from(document.querySelectorAll('input[name="pemasukan_ids[]"]'));
                if (!checkboxes.length) return;

                const auto = {{ (int) $potongan }};
                const toleransi = {{ (int) $toleransi }};
                const checkboxSemua = document.getElementById('pilih-semua');
                const kosongkan = document.getElementById('kosongkan');
                const totalDipilih = document.getElementById('total-dipilih');
                const rupiah = (n) => 'Rp ' + (n || 0).toLocaleString('id-ID');
                const angka = (id) => parseInt(String(document.getElementById(id)?.value || '').replace(/\D/g, ''), 10) || 0;

                const hitung = () => {
                    let dicatat = 0;
                    checkboxes.forEach((cb) => {
                        if (cb.checked && !cb.disabled) dicatat += parseInt(cb.dataset.nominal || '0', 10);
                    });

                    const potonganField = document.getElementById('potongan_kas_manual');
                    const potongan = potonganField && potonganField.value.replace(/\D/g, '') !== '' ? angka('potongan_kas_manual') : auto;
                    const biaya = angka('biaya_admin');
                    const diterima = angka('total_diterima');
                    const menunggu = document.getElementById('menunggu_settlement')?.checked ?? false;
                    const selisih = diterima - (dicatat - biaya - potongan);

                    totalDipilih.textContent = rupiah(dicatat);
                    document.getElementById('ringkasan-dicatat').textContent = rupiah(dicatat);
                    document.getElementById('ringkasan-potongan').textContent = rupiah(potongan);
                    document.getElementById('ringkasan-biaya').textContent = rupiah(biaya);
                    document.getElementById('ringkasan-diterima').textContent = rupiah(diterima);

                    const selisihEl = document.getElementById('ringkasan-selisih');
                    selisihEl.textContent = (selisih < 0 ? '−' : (selisih > 0 ? '+' : '')) + rupiah(Math.abs(selisih));
                    selisihEl.className = 'text-lg font-bold ' + (selisih < 0 ? 'text-red-600' : (selisih > 0 ? 'text-orange-700' : 'text-emerald-700'));

                    const status = document.getElementById('ringkasan-status');
                    if (menunggu) {
                        status.innerHTML = '<span class="badge badge-yellow">Menunggu settlement</span><p class="mt-1 text-slate-500">Tidak ada rekonsiliasi/insiden yang dibuat.</p>';
                    } else if (Math.abs(selisih) <= toleransi) {
                        status.innerHTML = '<span class="badge badge-green">Sesuai</span><p class="mt-1 text-slate-500">Selisih dalam toleransi {{ format_rupiah((int) $toleransi) }}.</p>';
                    } else {
                        status.innerHTML = '<span class="badge badge-red">Selisih</span><p class="mt-1 text-slate-500">Insiden otomatis akan dibuat.</p>';
                    }

                    document.getElementById('tombol-simpan').disabled = dicatat === 0;
                };

                checkboxes.forEach((cb) => cb.addEventListener('change', hitung));
                ['total_diterima', 'biaya_admin', 'potongan_kas_manual'].forEach((id) => document.getElementById(id)?.addEventListener('input', hitung));
                document.getElementById('menunggu_settlement')?.addEventListener('change', (event) => {
                    const penerimaan = document.getElementById('penerimaan-section');
                    const fields = ['tanggal_diterima', 'total_diterima', 'biaya_admin', 'potongan_kas_manual'];
                    fields.forEach((id) => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        el.disabled = event.target.checked;
                    });
                    penerimaan.querySelectorAll('input[type="file"]').forEach((el) => { el.disabled = event.target.checked; });
                });

                document.getElementById('isi-saran')?.addEventListener('click', (event) => {
                    const biaya = document.getElementById('biaya_admin');
                    biaya.value = event.target.dataset.saran;
                    hitung();
                });

                checkboxSemua?.addEventListener('click', () => {
                    checkboxes.forEach((cb) => { if (!cb.disabled) cb.checked = true; });
                    hitung();
                });
                kosongkan?.addEventListener('click', () => {
                    checkboxes.forEach((cb) => { if (!cb.disabled) cb.checked = false; });
                    hitung();
                });

                hitung();
            });
        </script>
    @endpush
@endsection
