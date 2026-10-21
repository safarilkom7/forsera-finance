@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', format_tanggal(now(), true).' · '.($units->firstWhere('id', $periode['unit_id'])?->nama ?? 'Unit Anda'))

@section('content')
    <div class="mb-6 rounded-3xl bg-gradient-to-br from-emerald-600 to-emerald-800 p-6 text-white shadow-lg sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">Selamat bertugas</p>
        <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Halo, {{ $user->name }}</h2>
        <p class="mt-2 max-w-xl text-sm text-emerald-100">Catat setiap pemasukan dan pengeluaran hari ini. Pastikan nominal dan metodenya benar agar mudah diverifikasi Owner.</p>
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('pemasukan.create') }}" class="btn bg-white text-emerald-700 hover:bg-emerald-50">
                <x-icon name="plus" class="h-4 w-4" /> Catat pemasukan
            </a>
            <a href="{{ route('pengeluaran.create') }}" class="btn border border-white/30 bg-white/10 text-white hover:bg-white/20">
                <x-icon name="expense" class="h-4 w-4" /> Catat pengeluaran
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="card card-pad">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pemasukan hari ini</p>
            <p class="stat-value text-lg sm:text-2xl">{{ format_rupiah($ringkasan_staf['total_pemasukan']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $ringkasan_staf['jumlah_pemasukan'] }} transaksi</p>
        </div>
        <div class="card card-pad">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pengeluaran hari ini</p>
            <p class="stat-value text-lg sm:text-2xl">{{ format_rupiah($ringkasan_staf['total_pengeluaran']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $ringkasan_staf['jumlah_pengeluaran'] }} transaksi</p>
        </div>
        <div class="card card-pad col-span-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Status input Anda</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @forelse ($ringkasan_staf['per_status'] as $status)
                    <x-status-badge :status="$status->status_verifikasi" />
                    <span class="text-xs text-slate-500">{{ $status->jumlah }} · {{ format_rupiah($status->total) }}</span>
                @empty
                    <p class="text-sm text-slate-500">Belum ada pemasukan yang Anda input hari ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="card card-pad">
            <h2 class="section-title">Pemasukan terbaru Anda</h2>
            <p class="muted mt-1">Hanya pemasukan yang Anda buat di unit yang ditugaskan.</p>
            <div class="mt-4 space-y-2">
                @php
                    $terbaru = \App\Models\Pemasukan::query()
                        ->where('dibuat_oleh', $user->id)
                        ->whereIn('unit_usaha_id', $user->unitUsaha()->select('unit_usaha.id'))
                        ->with(['unitUsaha', 'kategori', 'metode'])
                        ->latest('tanggal')
                        ->latest('id')
                        ->limit(5)
                        ->get();
                @endphp
                @forelse ($terbaru as $item)
                    <a href="{{ route('pemasukan.edit', $item) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2.5 text-sm hover:border-emerald-300 dark:border-slate-700">
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ $item->kategori?->nama }} · {{ $item->unitUsaha?->nama }}</p>
                            <p class="text-xs text-slate-500">{{ format_tanggal($item->tanggal) }} · {{ $item->metode?->nama }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-semibold">{{ format_rupiah($item->nominal) }}</p>
                            <x-status-badge :status="$item->status_verifikasi" class="mt-1" />
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Belum ada transaksi.</p>
                @endforelse
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="section-title">Yang perlu diingat</h2>
            <ul class="mt-3 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" /> Pemasukan yang sudah diverifikasi terkunci; hubungi Owner bila perlu koreksi.</li>
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" /> Lampirkan bukti untuk transaksi besar agar lebih cepat diverifikasi.</li>
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" /> Gunakan tombol “Simpan dan tambah lagi” saat mencatat banyak transaksi.</li>
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" /> Tanggal tidak boleh di masa depan dan periode yang sudah ditutup tidak bisa diubah.</li>
            </ul>
        </div>
    </div>
@endsection
