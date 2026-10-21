@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', $periode['label'].' · '.(collect($units)->firstWhere('id', $periode['unit_id'])?->nama ?? 'Semua unit'))

@section('content')
    @php
        $unitTerpilih = collect($units)->firstWhere('id', $periode['unit_id']);
        $perhatianCount = count($perhatian['belum_dicek']) + $perhatian['insiden']->count() + count($perhatian['settlement']) + $perhatian['tanpa_lampiran']->count() + $perhatian['menunggu_persetujuan']->count() + count($perhatian['lonjakan_harga']);
        $chartColors = ['#10b981', '#f97316', '#0ea5e9', '#8b5cf6', '#ef4444'];
    @endphp

    <form method="GET" action="{{ route('dashboard') }}" class="card mb-6 p-3 sm:p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="periode" class="label">Periode</label>
                    <x-pilihan id="periode" name="periode" class="input" data-auto-submit>
                        @foreach ($pilihan_periode as $key => $label)
                            <option value="{{ $key }}" @selected($periode['preset'] === $key)>{{ $label }}</option>
                        @endforeach
                    </x-pilihan>
                </div>
                <div>
                    <label for="unit" class="label">Unit usaha</label>
                    <x-pilihan id="unit" name="unit" class="input" data-auto-submit>
                        <option value="">Semua unit</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected($periode['unit_id'] === $unit->id)>{{ $unit->nama }}</option>
                        @endforeach
                    </x-pilihan>
                </div>
                <div>
                    <label for="dari" class="label">Dari tanggal</label>
                    <x-tanggal id="dari" name="dari" value="{{ request('dari', $periode['mulai']->toDateString()) }}" class="input" />
                </div>
                <div>
                    <label for="sampai" class="label">Sampai tanggal</label>
                    <x-tanggal id="sampai" name="sampai" value="{{ request('sampai', $periode['selesai']->toDateString()) }}" class="input" />
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary">Terapkan</button>
                @if ($periode['preset'] === 'custom')
                    <span class="badge badge-green">Custom</span>
                @endif
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Tanggal hanya dipakai saat memilih periode Custom. Periode berjalan mengikuti periode akuntansi (mulai tanggal {{ $periode['tanggal_mulai'] }}, bisa diubah di Pengaturan). Semua angka mengikuti zona waktu Asia/Jakarta.</p>
    </form>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
        <div class="card card-pad col-span-2 xl:col-span-1">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pemasukan</p>
                <span class="rounded-lg bg-emerald-50 p-1.5 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400"><x-icon name="income" class="h-4 w-4" /></span>
            </div>
            <p class="stat-value">{{ format_rupiah($ringkasan['total_pemasukan']) }}</p>
            <p class="mt-1 text-xs {{ $ringkasan['perubahan_pemasukan'] === null ? 'text-slate-500 dark:text-slate-400' : ($ringkasan['perubahan_pemasukan'] >= 0 ? 'text-emerald-700' : 'text-red-600') }}">
                @if ($ringkasan['perubahan_pemasukan'] === null)
                    Tidak ada data pembanding
                @else
                    {{ $ringkasan['perubahan_pemasukan'] >= 0 ? '▲' : '▼' }} {{ \App\Support\Format::persen(abs($ringkasan['perubahan_pemasukan'])) }} vs periode sebelumnya
                @endif
            </p>
        </div>

        <div class="card card-pad col-span-2 xl:col-span-1">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pengeluaran</p>
                <span class="rounded-lg bg-orange-50 p-1.5 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400"><x-icon name="expense" class="h-4 w-4" /></span>
            </div>
            <p class="stat-value">{{ format_rupiah($ringkasan['total_pengeluaran']) }}</p>
            <p class="mt-1 text-xs {{ $ringkasan['perubahan_pengeluaran'] === null ? 'text-slate-500 dark:text-slate-400' : ($ringkasan['perubahan_pengeluaran'] <= 0 ? 'text-emerald-700' : 'text-orange-700') }}">
                @if ($ringkasan['perubahan_pengeluaran'] === null)
                    Tidak ada data pembanding
                @else
                    {{ $ringkasan['perubahan_pengeluaran'] >= 0 ? '▲' : '▼' }} {{ \App\Support\Format::persen(abs($ringkasan['perubahan_pengeluaran'])) }} vs periode sebelumnya
                @endif
            </p>
        </div>

        <div class="card card-pad col-span-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Laba bersih</p>
            <p class="stat-value text-lg sm:text-2xl">{{ format_rupiah($ringkasan['laba']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Margin {{ \App\Support\Format::persen($ringkasan['margin']) }}</p>
        </div>

        <div class="card card-pad col-span-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Terverifikasi</p>
            <p class="stat-value text-lg sm:text-2xl">{{ \App\Support\Format::persen($ringkasan['terverifikasi_persen']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ format_rupiah($ringkasan['terverifikasi_nominal']) }} dari pemasukan</p>
        </div>

        <div class="card card-pad col-span-2 xl:col-span-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Selisih terbuka</p>
            <p class="stat-value text-lg sm:text-2xl {{ $ringkasan['selisih_terbuka_nominal'] > 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ format_rupiah($ringkasan['selisih_terbuka_nominal']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $ringkasan['selisih_terbuka_jumlah'] }} insiden belum tuntas</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="card card-pad">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="section-title">Tren pemasukan vs pengeluaran</h2>
                <span class="badge badge-gray">{{ $periode['label'] }}</span>
            </div>
            <div class="h-64"><canvas data-chart="chart-tren"></canvas></div>
        </div>

        <div class="card card-pad">
            <h2 class="section-title mb-4">Pemasukan per unit</h2>
            <div class="h-64"><canvas data-chart="chart-unit"></canvas></div>
        </div>

        <div class="card card-pad">
            <h2 class="section-title mb-4">Komposisi metode pembayaran</h2>
            <div class="h-64"><canvas data-chart="chart-metode"></canvas></div>
        </div>

        <div class="card card-pad">
            <h2 class="section-title mb-4">5 jenis pengeluaran teratas</h2>
            <div class="h-64"><canvas data-chart="chart-jenis"></canvas></div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-5">
                <h2 class="section-title">Ringkasan per unit</h2>
                @if ($ringkasan['selisih_dihapuskan'] > 0)
                    <span class="text-xs text-slate-500">Selisih dihapuskan: {{ format_rupiah($ringkasan['selisih_dihapuskan']) }}</span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Unit</th>
                        <th class="text-right">Pemasukan</th>
                        <th class="text-right">Pengeluaran</th>
                        <th class="text-right">Laba</th>
                        <th class="text-right">Margin</th>
                        <th class="text-right">Terverifikasi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($tabel_unit as $baris)
                        <tr>
                            <td class="font-medium text-slate-800 dark:text-slate-100">{{ $baris['unit']->nama }}</td>
                            <td class="text-right">{{ format_rupiah($baris['pemasukan']) }}</td>
                            <td class="text-right">{{ format_rupiah($baris['pengeluaran']) }}</td>
                            <td class="text-right font-semibold {{ $baris['laba'] < 0 ? 'text-red-600' : 'text-emerald-700 dark:text-emerald-400' }}">{{ format_rupiah($baris['laba']) }}</td>
                            <td class="text-right">{{ \App\Support\Format::persen($baris['margin']) }}</td>
                            <td class="text-right">{{ \App\Support\Format::persen($baris['terverifikasi_persen']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada unit usaha aktif.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-5">
                <h2 class="section-title">Perlu perhatian</h2>
                <span class="badge {{ $perhatianCount > 0 ? 'badge-red' : 'badge-green' }}">{{ $perhatianCount }} item</span>
            </div>
            <div class="max-h-[32rem] space-y-4 overflow-y-auto p-4 sm:p-5">
                @if ($perhatian['belum_dicek'] !== [])
                    <div>
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"><x-icon name="clock" class="h-4 w-4" /> Belum diverifikasi</p>
                        <div class="space-y-2">
                            @foreach ($perhatian['belum_dicek'] as $item)
                                <a href="{{ route('rekonsiliasi.create', ['unit' => $item['unit_id'], 'metode' => $item['metode_id'], 'mulai' => \Illuminate\Support\Carbon::parse($item['tertua'])->toDateString(), 'selesai' => now()->toDateString()]) }}" class="block rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10">
                                    <p class="font-semibold text-amber-900 dark:text-amber-200">{{ $item['unit'] }} · {{ $item['metode'] }}</p>
                                    <p class="mt-0.5 text-amber-700 dark:text-amber-300/80">{{ $item['jumlah'] }} transaksi · {{ format_rupiah($item['total']) }} · tertua {{ format_tanggal($item['tertua']) }}</p>
                                    <p class="mt-1 font-semibold text-amber-800 dark:text-amber-300">Verifikasi sekarang →</p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($perhatian['insiden']->isNotEmpty())
                    <div>
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"><x-icon name="warning" class="h-4 w-4" /> Insiden terbuka</p>
                        <div class="space-y-2">
                            @foreach ($perhatian['insiden'] as $insiden)
                                <a href="{{ route('insiden.show', $insiden) }}" class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 p-2.5 text-xs hover:border-red-300 dark:border-slate-700">
                                    <div>
                                        <p class="font-semibold">{{ $insiden->kode }}</p>
                                        <p class="text-slate-500">{{ $insiden->rekonsiliasi?->unitUsaha?->nama }} · {{ $insiden->arah?->getLabel() }}</p>
                                    </div>
                                    <span class="font-semibold text-red-600 dark:text-red-400">{{ format_rupiah($insiden->nominal_selisih) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($perhatian['settlement'] !== [])
                    <div>
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"><x-icon name="banknotes" class="h-4 w-4" /> Menunggu settlement</p>
                        @foreach ($perhatian['settlement'] as $item)
                            <div class="mb-2 rounded-xl border border-slate-200 p-2.5 text-xs dark:border-slate-700">
                                <p class="font-semibold">{{ $item['unit'] }} · {{ $item['metode'] }}</p>
                                <p class="text-slate-500">{{ $item['jumlah'] }} transaksi · {{ format_rupiah($item['total']) }} · tertua {{ format_tanggal($item['tertua']) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($perhatian['menunggu_persetujuan']->isNotEmpty())
                    <div>
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"><x-icon name="check" class="h-4 w-4" /> Menunggu persetujuan</p>
                        <div class="space-y-2">
                            @foreach ($perhatian['menunggu_persetujuan'] as $pengeluaran)
                                <div class="rounded-xl border border-slate-200 p-2.5 text-xs dark:border-slate-700">
                                    <p class="font-semibold">{{ $pengeluaran->unitUsaha->nama }}</p>
                                    <p class="text-slate-500">{{ $pengeluaran->jenis?->nama }} · {{ format_rupiah($pengeluaran->total_nominal) }} · {{ $pengeluaran->dibuatOleh?->name }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($perhatian['tanpa_lampiran']->isNotEmpty())
                    <div>
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"><x-icon name="file-text" class="h-4 w-4" /> Pengeluaran tanpa lampiran</p>
                        <div class="space-y-1.5">
                            @foreach ($perhatian['tanpa_lampiran'] as $pengeluaran)
                                <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs dark:bg-slate-800/60">{{ $pengeluaran->unitUsaha->nama }} · {{ format_rupiah($pengeluaran->total_nominal) }} · {{ format_tanggal($pengeluaran->tanggal) }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($perhatian['lonjakan_harga'] !== [])
                    <div>
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"><x-icon name="chart" class="h-4 w-4" /> Lonjakan harga</p>
                        @foreach ($perhatian['lonjakan_harga'] as $lonjakan)
                            <p class="mb-1.5 rounded-lg bg-orange-50 px-3 py-2 text-xs text-orange-800 dark:bg-orange-500/10 dark:text-orange-300">{{ $lonjakan['item'] }}: {{ format_rupiah($lonjakan['harga']) }} (rata-rata {{ format_rupiah($lonjakan['rata_rata']) }}, +{{ $lonjakan['persen'] }}%)</p>
                        @endforeach
                    </div>
                @endif

                @if ($perhatian['selisih_dihapuskan'] > 0)
                    <p class="rounded-xl bg-slate-100 px-3 py-2 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">Selisih dihapuskan periode ini: {{ format_rupiah($perhatian['selisih_dihapuskan']) }}</p>
                @endif

                @if ($perhatianCount === 0)
                    <div class="py-8 text-center">
                        <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400"><x-icon name="check" class="h-6 w-6" /></div>
                        <p class="mt-3 text-sm font-semibold">Semua terkendali</p>
                        <p class="mt-1 text-xs text-slate-500">Tidak ada hal yang perlu ditindaklanjuti.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script type="application/json" id="chart-tren">
        {!! json_encode([
            'type' => 'line',
            'data' => [
                'labels' => $tren['labels'],
                'datasets' => [
                    ['label' => 'Pemasukan', 'data' => $tren['pemasukan'], 'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16,185,129,.12)', 'fill' => true, 'tension' => .35, 'pointRadius' => 2],
                    ['label' => 'Pengeluaran', 'data' => $tren['pengeluaran'], 'borderColor' => '#f97316', 'backgroundColor' => 'rgba(249,115,22,.10)', 'fill' => true, 'tension' => .35, 'pointRadius' => 2],
                ],
            ],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['labels' => ['color' => '#94a3b8']]], 'scales' => ['x' => ['grid' => ['display' => false], 'ticks' => ['color' => '#94a3b8', 'maxRotation' => 0]], 'y' => ['ticks' => ['color' => '#94a3b8'], 'grid' => ['color' => 'rgba(148,163,184,.15)']]]],
        ], JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/json" id="chart-unit">
        {!! json_encode([
            'type' => 'bar',
            'data' => ['labels' => $pemasukan_unit_chart['labels'], 'datasets' => array_map(fn ($d) => $d + ['stack' => 'unit', 'borderRadius' => 4], $pemasukan_unit_chart['datasets'])],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['labels' => ['color' => '#94a3b8']]], 'scales' => ['x' => ['stacked' => true, 'grid' => ['display' => false], 'ticks' => ['color' => '#94a3b8', 'maxRotation' => 0]], 'y' => ['stacked' => true, 'ticks' => ['color' => '#94a3b8'], 'grid' => ['color' => 'rgba(148,163,184,.15)']]]],
        ], JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/json" id="chart-metode">
        {!! json_encode([
            'type' => 'doughnut',
            'data' => ['labels' => $komposisi_metode['labels'], 'datasets' => [['data' => $komposisi_metode['data'], 'backgroundColor' => $chartColors, 'borderWidth' => 0]]],
            'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'cutout' => '62%', 'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['color' => '#94a3b8', 'boxWidth' => 10]]]],
        ], JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/json" id="chart-jenis">
        {!! json_encode([
            'type' => 'bar',
            'data' => ['labels' => $top_jenis['labels'], 'datasets' => [['label' => 'Total', 'data' => $top_jenis['data'], 'backgroundColor' => '#f97316', 'borderRadius' => 6]]],
            'options' => ['indexAxis' => 'y', 'responsive' => true, 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['ticks' => ['color' => '#94a3b8'], 'grid' => ['color' => 'rgba(148,163,184,.15)']], 'y' => ['ticks' => ['color' => '#94a3b8'], 'grid' => ['display' => false]]]],
        ], JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection
