@extends('layouts.app')

@section('title', 'Notifikasi')
@section('subtitle', 'Pengingat verifikasi, settlement, dan persetujuan')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="muted">Notifikasi disimpan di dalam aplikasi dan tidak dikirim lewat email/WhatsApp.</p>
        @if ($data->count() > 0)
            <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                @csrf
                <button class="btn-secondary btn-sm">Tandai semua dibaca</button>
            </form>
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($data as $n)
                <form method="POST" action="{{ route('notifikasi.baca', $n->id) }}" class="block">
                    @csrf
                    <button type="submit" class="flex w-full items-start gap-3 px-4 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $n->read_at ? 'bg-slate-100 text-slate-500 dark:text-slate-400 dark:bg-slate-800' : 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' }}">
                            <x-icon name="{{ $n->data['icon'] ?? 'bell' }}" class="h-4 w-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center justify-between gap-1">
                                <span class="text-sm font-semibold {{ $n->read_at ? 'text-slate-600 dark:text-slate-300' : 'text-slate-900 dark:text-white' }}">{{ $n->data['title'] ?? 'Notifikasi' }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ $n->created_at->diffForHumans() }}</span>
                            </span>
                            <span class="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">{{ $n->data['body'] ?? '' }}</span>
                            @if (! $n->read_at)
                                <span class="mt-1 inline-block text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">Belum dibaca · klik untuk membuka</span>
                            @endif
                        </span>
                    </button>
                </form>
            @empty
                <div class="p-10 text-center">
                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-500 dark:text-slate-400 dark:bg-slate-800"><x-icon name="bell" class="h-6 w-6" /></div>
                    <p class="mt-3 font-semibold">Belum ada notifikasi</p>
                    <p class="mt-1 text-sm text-slate-500">Notifikasi muncul saat ada insiden, keterlambatan verifikasi/settlement, atau pengeluaran menunggu persetujuan.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
@endsection
