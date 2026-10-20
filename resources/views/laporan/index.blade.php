@extends('layouts.app')

@section('title', 'Laporan Keuangan')
@section('subtitle', $periode['label'].' · '.(collect($units)->firstWhere('id', $periode['unit_id'])?->nama ?? 'Semua unit'))

@section('content')
    <form method="GET" action="{{ route('laporan.index') }}" class="card mb-5 card-pad">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="periode" class="label">Periode</label>
                <select id="periode" name="periode" class="input" data-auto-submit>
                    @foreach (['hari_ini' => 'Hari ini', '7_hari' => '7 hari terakhir', 'bulan_ini' => 'Bulan ini', 'bulan_lalu' => 'Bulan lalu', 'tahun_ini' => 'Tahun ini', 'custom' => 'Custom'] as $key => $label)
                        <option value="{{ $key }}" @selected($periode['preset'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="unit" class="label">Unit</label>
                <select id="unit" name="unit" class="input" data-auto-submit>
                    <option value="">Semua unit</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected($periode['unit_id'] === $unit->id)>{{ $unit->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dari" class="label">Dari</label>
                <input type="date" id="dari" name="dari" value="{{ request('dari', $periode['mulai']->toDateString()) }}" class="input">
            </div>
            <div>
                <label for="sampai" class="label">Sampai</label>
                <input type="date" id="sampai" name="sampai" value="{{ request('sampai', $periode['selesai']->toDateString()) }}" class="input">
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button class="btn-primary">Terapkan</button>
            <a href="{{ route('laporan.excel', request()->query()) }}" class="btn-secondary"><x-icon name="download" class="h-4 w-4" /> Excel</a>
            <a href="{{ route('laporan.pdf', request()->query()) }}" class="btn-secondary"><x-icon name="download" class="h-4 w-4" /> PDF</a>
        </div>
    </form>

    <nav class="mb-5 flex flex-wrap gap-2 text-xs">
        <a href="#laba-rugi" class="rounded-full bg-white px-3 py-1.5 font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700">Laba rugi</a>
        <a href="#pemasukan" class="rounded-full bg-white px-3 py-1.5 font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700">Pemasukan</a>
        <a href="#pengeluaran" class="rounded-full bg-white px-3 py-1.5 font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700">Pengeluaran</a>
        <a href="#rekonsiliasi" class="rounded-full bg-white px-3 py-1.5 font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700">Rekonsiliasi</a>
        <a href="#buku-kas" class="rounded-full bg-white px-3 py-1.5 font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700">Buku kas</a>
    </nav>

    <section id="laba-rugi" class="card mb-5 overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-5">
            <h2 class="section-title">1. Laba rugi sederhana</h2>
            <p class="hint">Pemasukan − pengeluaran disetujui per unit, termasuk gabungan.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Unit</th><th class="text-right">Pemasukan</th><th class="text-right">Pengeluaran</th><th class="text-right">Laba bersih</th><th class="text-right">Margin</th></tr></thead>
                <tbody>
                @foreach ($laba_rugi as $baris)
                    <tr class="{{ $baris['unit'] === 'GABUNGAN' ? 'bg-slate-50 font-semibold dark:bg-slate-800/50' : '' }}">
                        <td>{{ $baris['unit'] }}</td>
                        <td class="text-right">{{ format_rupiah($baris['pemasukan']) }}</td>
                        <td class="text-right">{{ format_rupiah($baris['pengeluaran']) }}</td>
                        <td class="text-right {{ $baris['laba'] < 0 ? 'text-red-600' : 'text-emerald-700 dark:text-emerald-400' }}">{{ format_rupiah($baris['laba']) }}</td>
                        <td class="text-right">{{ \App\Support\Format::persen($baris['margin']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section id="pemasukan" class="card mb-5 overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-5">
            <h2 class="section-title">2. Pemasukan per unit × kategori × metode</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Unit</th><th>Kategori</th><th>Metode</th><th class="text-right">Transaksi</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                @forelse ($pemasukan_kategori as $baris)
                    <tr>
                        <td>{{ $baris->unit }}</td>
                        <td>{{ $baris->kategori }}</td>
                        <td>{{ $baris->metode }}</td>
                        <td class="text-right">{{ $baris->transaksi }}</td>
                        <td class="text-right font-semibold">{{ format_rupiah($baris->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">Tidak ada pemasukan pada periode ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section id="pengeluaran" class="mb-5 grid gap-4 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800"><h2 class="section-title">3a. Pengeluaran per jenis</h2></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Jenis</th><th class="text-right">Transaksi</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                    @forelse ($pengeluaran_jenis as $baris)
                        <tr><td>{{ $baris->jenis }}</td><td class="text-right">{{ $baris->transaksi }}</td><td class="text-right font-semibold">{{ format_rupiah($baris->total) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-8 text-center text-slate-500">Tidak ada pengeluaran.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800"><h2 class="section-title">3b. Pengeluaran per item</h2></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Item</th><th class="text-right">Jumlah</th><th class="text-right">Harga rata-rata</th><th class="text-right">Terendah</th><th class="text-right">Tertinggi</th></tr></thead>
                    <tbody>
                    @forelse ($pengeluaran_item as $baris)
                        <tr>
                            <td>{{ $baris->item }} <span class="text-xs text-slate-400">({{ $baris->satuan }})</span></td>
                            <td class="text-right">{{ \App\Support\Format::angka($baris->jumlah) }}</td>
                            <td class="text-right">{{ format_rupiah(round($baris->harga_rata)) }}</td>
                            <td class="text-right">{{ format_rupiah($baris->harga_min) }}</td>
                            <td class="text-right">{{ format_rupiah($baris->harga_maks) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-500">Tidak ada detail item.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="rekonsiliasi" class="mb-5 grid gap-4 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800"><h2 class="section-title">4a. Rekonsiliasi per metode</h2></div>
            <table class="table">
                <thead><tr><th>Metode</th><th class="text-right">Jumlah</th><th class="text-right">Dicatat</th><th class="text-right">Diterima</th><th class="text-right">Selisih</th></tr></thead>
                <tbody>
                @forelse ($rekonsiliasi['per_metode'] as $baris)
                    <tr><td>{{ $baris->metode }}</td><td class="text-right">{{ $baris->jumlah }}</td><td class="text-right">{{ format_rupiah($baris->dicatat) }}</td><td class="text-right">{{ format_rupiah($baris->diterima) }}</td><td class="text-right font-semibold {{ $baris->selisih < 0 ? 'text-red-600' : ($baris->selisih > 0 ? 'text-orange-600' : '') }}">{{ \App\Support\Format::rupiahSigned($baris->selisih) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">Tidak ada rekonsiliasi.</td></tr>
                @endforelse
                </tbody>
            </table>

            <div class="border-y border-slate-100 px-4 py-3 dark:border-slate-800"><h3 class="font-semibold">4b. Rekonsiliasi per unit</h3></div>
            <table class="table">
                <thead><tr><th>Unit</th><th class="text-right">Jumlah</th><th class="text-right">Selisih</th><th class="text-right">Selisih terbuka</th></tr></thead>
                <tbody>
                @forelse ($rekonsiliasi['per_unit'] as $baris)
                    <tr><td>{{ $baris->unit }}</td><td class="text-right">{{ $baris->jumlah }}</td><td class="text-right font-semibold">{{ \App\Support\Format::rupiahSigned($baris->selisih) }}</td><td class="text-right">{{ $baris->selisih_terbuka }}</td></tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-slate-500">Tidak ada rekonsiliasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800"><h2 class="section-title">4c. Insiden &amp; penyelesaian</h2></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Kode</th><th>Arah</th><th class="text-right">Selisih</th><th class="text-right">Terselesaikan</th><th>Status</th><th>Penyelesaian</th></tr></thead>
                    <tbody>
                    @forelse ($rekonsiliasi['insiden'] as $insiden)
                        <tr>
                            <td><a href="{{ route('insiden.show', $insiden) }}" class="font-medium text-emerald-600 hover:underline dark:text-emerald-400">{{ $insiden->kode }}</a></td>
                            <td class="text-xs">{{ $insiden->arah?->getLabel() }}</td>
                            <td class="text-right">{{ format_rupiah($insiden->nominal_selisih) }}</td>
                            <td class="text-right">{{ format_rupiah($insiden->nominal_terselesaikan) }}</td>
                            <td><x-status-badge :status="$insiden->status" /></td>
                            <td class="text-xs">{{ $insiden->tipe_penyelesaian?->getLabel() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Tidak ada insiden pada periode ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="buku-kas" class="space-y-4">
        <div class="card card-pad">
            <h2 class="section-title">5. Buku kas harian per unit</h2>
            <p class="hint">Saldo berjalan relatif terhadap awal periode (saldo awal = 0).</p>
        </div>
        @forelse ($buku_kas as $unit)
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    <h3 class="font-semibold">{{ $unit['unit']->nama }}</h3>
                    <span class="text-xs text-slate-500">Saldo akhir {{ format_rupiah(collect($unit['baris'])->last()['saldo'] ?? 0) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Tanggal</th><th>Keterangan</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Saldo</th></tr></thead>
                        <tbody>
                        @forelse ($unit['baris'] as $baris)
                            <tr>
                                <td class="whitespace-nowrap">{{ format_tanggal($baris['tanggal']) }}</td>
                                <td>{{ $baris['keterangan'] }}</td>
                                <td class="text-right text-emerald-700 dark:text-emerald-400">{{ $baris['masuk'] ? format_rupiah($baris['masuk']) : '—' }}</td>
                                <td class="text-right text-orange-600">{{ $baris['keluar'] ? format_rupiah($baris['keluar']) : '—' }}</td>
                                <td class="text-right font-semibold">{{ format_rupiah($baris['saldo']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-500">Tidak ada transaksi.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">Tidak ada unit untuk ditampilkan.</p>
        @endforelse
    </section>
@endsection
