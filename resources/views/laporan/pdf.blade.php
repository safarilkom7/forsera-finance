<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan {{ $branding['brand'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; margin: 22px; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 11px; margin: 16px 0 5px; padding-bottom: 3px; border-bottom: 1px solid #d1d5db; }
        h3 { font-size: 9.5px; margin: 10px 0 4px; }
        .muted { color: #6b7280; font-size: 8.5px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #059669; padding-bottom: 6px; margin-bottom: 6px; }
        .brand { font-weight: bold; font-size: 11px; color: #059669; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th { background: #f3f4f6; text-align: left; padding: 3px 4px; font-size: 8px; text-transform: uppercase; border-bottom: 1px solid #d1d5db; }
        td { padding: 3px 4px; border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .green { color: #047857; }
        .red { color: #b91c1c; }
        .orange { color: #c2410c; }
        .total-row td { background: #f9fafb; font-weight: bold; }
        .page-break { page-break-before: always; }
        .footer { margin-top: 12px; font-size: 7.5px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Laporan Keuangan {{ $branding['brand'] }}</h1>
            <p class="muted">{{ $periode['label'] }} · {{ format_tanggal($periode['mulai']) }} – {{ format_tanggal($periode['selesai']) }} · {{ collect($units)->firstWhere('id', $periode['unit_id'])?->nama ?? 'Semua unit' }}</p>
        </div>
        <div class="brand">{{ mb_strtoupper($branding['brand']) }}</div>
    </div>

    <h2>1. Laba Rugi Sederhana</h2>
    <table>
        <thead><tr><th>Unit</th><th class="right">Pemasukan</th><th class="right">Pengeluaran</th><th class="right">Laba Bersih</th><th class="right">Margin</th></tr></thead>
        <tbody>
        @foreach ($laba_rugi as $baris)
            <tr class="{{ $baris['unit'] === 'GABUNGAN' ? 'total-row' : '' }}">
                <td>{{ $baris['unit'] }}</td>
                <td class="right">{{ format_rupiah($baris['pemasukan']) }}</td>
                <td class="right">{{ format_rupiah($baris['pengeluaran']) }}</td>
                <td class="right bold {{ $baris['laba'] < 0 ? 'red' : 'green' }}">{{ format_rupiah($baris['laba']) }}</td>
                <td class="right">{{ \App\Support\Format::persen($baris['margin']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>2. Pemasukan per Unit × Kategori × Metode</h2>
    <table>
        <thead><tr><th>Unit</th><th>Kategori</th><th>Metode</th><th class="right">Transaksi</th><th class="right">Total</th></tr></thead>
        <tbody>
        @forelse ($pemasukan_kategori as $baris)
            <tr><td>{{ $baris->unit }}</td><td>{{ $baris->kategori }}</td><td>{{ $baris->metode }}</td><td class="right">{{ $baris->transaksi }}</td><td class="right">{{ format_rupiah($baris->total) }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>3. Pengeluaran</h2>
    <h3>3a. Per jenis</h3>
    <table>
        <thead><tr><th>Jenis</th><th class="right">Transaksi</th><th class="right">Total</th></tr></thead>
        <tbody>
        @forelse ($pengeluaran_jenis as $baris)
            <tr><td>{{ $baris->jenis }}</td><td class="right">{{ $baris->transaksi }}</td><td class="right">{{ format_rupiah($baris->total) }}</td></tr>
        @empty
            <tr><td colspan="3" class="muted">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3>3b. Per item</h3>
    <table>
        <thead><tr><th>Item</th><th>Satuan</th><th class="right">Jumlah</th><th class="right">Harga Rata-rata</th><th class="right">Terendah</th><th class="right">Tertinggi</th><th class="right">Total</th></tr></thead>
        <tbody>
        @forelse ($pengeluaran_item as $baris)
            <tr>
                <td>{{ $baris->item }}</td>
                <td>{{ $baris->satuan }}</td>
                <td class="right">{{ \App\Support\Format::angka($baris->jumlah) }}</td>
                <td class="right">{{ format_rupiah(round($baris->harga_rata)) }}</td>
                <td class="right">{{ format_rupiah($baris->harga_min) }}</td>
                <td class="right">{{ format_rupiah($baris->harga_maks) }}</td>
                <td class="right">{{ format_rupiah($baris->total) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>
    <h2>4. Rekonsiliasi</h2>
    <h3>4a. Per metode</h3>
    <table>
        <thead><tr><th>Metode</th><th class="right">Jumlah</th><th class="right">Dicatat</th><th class="right">Diterima</th><th class="right">Selisih</th></tr></thead>
        <tbody>
        @forelse ($rekonsiliasi['per_metode'] as $baris)
            <tr><td>{{ $baris->metode }}</td><td class="right">{{ $baris->jumlah }}</td><td class="right">{{ format_rupiah($baris->dicatat) }}</td><td class="right">{{ format_rupiah($baris->diterima) }}</td><td class="right {{ $baris->selisih < 0 ? 'red' : ($baris->selisih > 0 ? 'orange' : '') }}">{{ \App\Support\Format::rupiahSigned($baris->selisih) }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3>4b. Per unit</h3>
    <table>
        <thead><tr><th>Unit</th><th class="right">Jumlah</th><th class="right">Selisih</th><th class="right">Selisih Terbuka</th></tr></thead>
        <tbody>
        @forelse ($rekonsiliasi['per_unit'] as $baris)
            <tr><td>{{ $baris->unit }}</td><td class="right">{{ $baris->jumlah }}</td><td class="right">{{ \App\Support\Format::rupiahSigned($baris->selisih) }}</td><td class="right">{{ $baris->selisih_terbuka }}</td></tr>
        @empty
            <tr><td colspan="4" class="muted">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3>4c. Insiden dan penyelesaian</h3>
    <table>
        <thead><tr><th>Kode</th><th>Unit</th><th>Jenis</th><th>Arah</th><th class="right">Selisih</th><th class="right">Terselesaikan</th><th>Status</th><th>Penyelesaian</th></tr></thead>
        <tbody>
        @forelse ($rekonsiliasi['insiden'] as $insiden)
            <tr>
                <td>{{ $insiden->kode }}</td>
                <td>{{ $insiden->rekonsiliasi?->unitUsaha?->nama }}</td>
                <td>{{ $insiden->jenis?->getLabel() }}</td>
                <td>{{ $insiden->arah?->getLabel() }}</td>
                <td class="right">{{ format_rupiah($insiden->nominal_selisih) }}</td>
                <td class="right">{{ format_rupiah($insiden->nominal_terselesaikan) }}</td>
                <td>{{ $insiden->status?->getLabel() }}</td>
                <td>{{ $insiden->tipe_penyelesaian?->getLabel() ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>5. Buku Kas Harian</h2>
    @foreach ($buku_kas as $unit)
        <h3>{{ $unit['unit']->nama }}</h3>
        <table>
            <thead><tr><th>Tanggal</th><th>Keterangan</th><th class="right">Masuk</th><th class="right">Keluar</th><th class="right">Saldo</th></tr></thead>
            <tbody>
            @forelse ($unit['baris'] as $baris)
                <tr>
                    <td>{{ format_tanggal($baris['tanggal']) }}</td>
                    <td>{{ $baris['keterangan'] }}</td>
                    <td class="right">{{ $baris['masuk'] ? format_rupiah($baris['masuk']) : '—' }}</td>
                    <td class="right">{{ $baris['keluar'] ? format_rupiah($baris['keluar']) : '—' }}</td>
                    <td class="right bold">{{ format_rupiah($baris['saldo']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Tidak ada transaksi.</td></tr>
            @endforelse
            </tbody>
        </table>
    @endforeach

    <p class="footer">Dibuat {{ format_tanggal(now()) }} {{ now()->format('H:i') }} WIB oleh {{ auth()->user()?->name }}. {{ $branding['nama'] }}.</p>
</body>
</html>
