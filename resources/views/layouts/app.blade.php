<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ $branding['nama'] }}</title>
    @if ($branding['logo_url'])
        <link rel="icon" href="{{ $branding['logo_url'] }}">
    @endif
    <script>
        try {
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full">
@php
    $pengguna = auth()->user();
    $unread = $pengguna->unreadNotifications()->count();
    $notifikasi = $pengguna->notifications()->latest()->limit(5)->get();
    $isOwner = $pengguna->isOwner();
    $isInternal = $isOwner || $pengguna->isAdmin();
@endphp

<div class="min-h-screen lg:pl-64">
    <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 dark:border-slate-800 dark:bg-slate-900 lg:translate-x-0">
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-5 dark:border-slate-800">
            @if ($branding['logo_url'])
                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['brand'] }}"
                     class="h-10 w-10 shrink-0 rounded-xl bg-white object-contain p-1 ring-1 ring-slate-200 dark:ring-slate-700">
            @else
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-600 text-sm font-black text-white">{{ $branding['inisial'] }}</div>
            @endif
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $branding['brand'] }}</p>
                @if ($branding['title'] !== '')
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $branding['title'] }}</p>
                @endif
            </div>
            <button type="button" class="ml-auto rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden" data-sidebar-close aria-label="Tutup menu">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
            <div>
                <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Operasional</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                        <x-icon name="dashboard" /> Dashboard
                    </a>
                    <a href="{{ route('pemasukan.index') }}" class="nav-link {{ request()->routeIs('pemasukan.*') ? 'nav-link-active' : '' }}">
                        <x-icon name="income" /> Pemasukan
                    </a>
                    <a href="{{ route('pengeluaran.index') }}" class="nav-link {{ request()->routeIs('pengeluaran.*') ? 'nav-link-active' : '' }}">
                        <x-icon name="expense" /> Pengeluaran
                    </a>
                    @if ($isInternal)
                        <a href="{{ route('rekonsiliasi.index') }}" class="nav-link {{ request()->routeIs('rekonsiliasi.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="scale" /> Rekonsiliasi
                        </a>
                        <a href="{{ route('insiden.index') }}" class="nav-link {{ request()->routeIs('insiden.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="warning" /> Insiden
                        </a>
                    @endif
                </div>
            </div>

            @if ($isInternal)
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Analitik</p>
                    <a href="{{ route('laporan.index') }}" class="nav-link {{ request()->routeIs('laporan.*') ? 'nav-link-active' : '' }}">
                        <x-icon name="chart" /> Laporan
                    </a>
                </div>

                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Master Data</p>
                    <div class="space-y-1">
                        <a href="{{ route('master.unit.index') }}" class="nav-link {{ request()->routeIs('master.unit.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="building" /> Unit Usaha
                        </a>
                        <a href="{{ route('master.metode.index') }}" class="nav-link {{ request()->routeIs('master.metode.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="card" /> Metode Bayar
                        </a>
                        <a href="{{ route('master.kategori.index') }}" class="nav-link {{ request()->routeIs('master.kategori.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="tag" /> Kategori Pemasukan
                        </a>
                        <a href="{{ route('master.jenis.index') }}" class="nav-link {{ request()->routeIs('master.jenis.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="list" /> Jenis Pengeluaran
                        </a>
                        <a href="{{ route('master.satuan.index') }}" class="nav-link {{ request()->routeIs('master.satuan.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="ruler" /> Satuan
                        </a>
                        <a href="{{ route('master.item.index') }}" class="nav-link {{ request()->routeIs('master.item.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="cube" /> Item
                        </a>
                    </div>
                </div>
            @endif

            @if ($isOwner)
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administrasi</p>
                    <div class="space-y-1">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="users" /> Pengguna
                        </a>
                        <a href="{{ route('pengaturan.index') }}" class="nav-link {{ request()->routeIs('pengaturan.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="settings" /> Pengaturan
                        </a>
                        <a href="{{ route('periode.index') }}" class="nav-link {{ request()->routeIs('periode.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="lock" /> Tutup Periode
                        </a>
                        <a href="{{ route('activity.index') }}" class="nav-link {{ request()->routeIs('activity.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="file-text" /> Log Aktivitas
                        </a>
                    </div>
                </div>
            @endif

            <div>
                <a href="{{ route('notifikasi.index') }}" class="nav-link {{ request()->routeIs('notifikasi.*') ? 'nav-link-active' : '' }}">
                    <x-icon name="bell" /> Notifikasi
                    @if ($unread > 0)
                        <span class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $unread }}</span>
                    @endif
                </a>
            </div>
        </nav>

        <div class="border-t border-slate-200 p-4 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs capitalize text-slate-500 dark:text-slate-400">{{ str_replace('_', ' ', auth()->user()->getRoleNames()->first() ?? '-') }}</p>
                </div>
            </div>
        </div>
    </aside>

    <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-900/60 backdrop-blur-sm lg:hidden"></div>

    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
        <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
            <button type="button" class="rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden" data-sidebar-open aria-label="Buka menu">
                <x-icon name="menu" class="h-5 w-5" />
            </button>

            <div class="min-w-0">
                <h1 class="truncate text-base font-semibold text-slate-900 dark:text-white sm:text-lg">@yield('title', 'Dashboard')</h1>
                @hasSection('subtitle')
                    <p class="hidden truncate text-xs text-slate-500 dark:text-slate-400 sm:block">@yield('subtitle')</p>
                @endif
            </div>

            <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
                <button type="button" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800" data-dark-toggle aria-label="Ganti mode gelap">
                    <x-icon name="sun" class="h-5 w-5 dark:hidden" />
                    <x-icon name="moon" class="hidden h-5 w-5 dark:block" />
                </button>

                <div class="relative">
                    <button type="button" class="relative rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800" data-dropdown-toggle="notif-menu" aria-label="Notifikasi">
                        <x-icon name="bell" class="h-5 w-5" />
                        @if ($unread > 0)
                            <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"></span>
                        @endif
                    </button>
                    <div id="notif-menu" data-dropdown class="absolute right-0 mt-2 hidden w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                            <p class="text-sm font-semibold">Notifikasi</p>
                            <span class="text-xs text-slate-500">{{ $unread }} belum dibaca</span>
                        </div>
                        <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                            @forelse ($notifikasi as $n)
                                <a href="{{ $n->data['url'] ?? route('notifikasi.index') }}" class="block px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $n->data['title'] ?? 'Notifikasi' }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ $n->data['body'] ?? '' }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $n->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <p class="px-4 py-6 text-center text-sm text-slate-500">Belum ada notifikasi.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('notifikasi.index') }}" class="block border-t border-slate-100 px-4 py-3 text-center text-sm font-semibold text-emerald-600 hover:bg-slate-50 dark:border-slate-800 dark:text-emerald-400 dark:hover:bg-slate-800/60">Lihat semua</a>
                    </div>
                </div>

                <div class="relative">
                    <button type="button" class="flex items-center gap-2 rounded-xl p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800" data-dropdown-toggle="user-menu">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-600 text-xs font-bold text-white">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <x-icon name="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block" />
                    </button>
                    <div id="user-menu" data-dropdown class="absolute right-0 mt-2 hidden w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profil.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
                            <x-icon name="user" class="h-4 w-4" /> Profil &amp; Password
                        </a>
                        <form method="POST" action="{{ route('logout') }}"
                              data-confirm="Sesi Anda akan diakhiri. Anda perlu masuk kembali untuk melanjutkan."
                              data-confirm-title="Keluar dari aplikasi?"
                              data-confirm-severity="info"
                              data-confirm-label="Ya, keluar">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                                <x-icon name="logout" class="h-4 w-4" /> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @if (session('sukses'))
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300" role="status">
                <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0" />
                <div>{!! nl2br(e(session('sukses'))) !!}</div>
            </div>
        @endif
        @if (session('gagal'))
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300" role="alert">
                <x-icon name="alert-circle" class="mt-0.5 h-5 w-5 shrink-0" />
                <div>{!! nl2br(e(session('gagal'))) !!}</div>
            </div>
        @endif
        @if ($errors->any() && ! isset($hideErrorSummary))
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300" role="alert">
                <p class="font-semibold">Periksa kembali data berikut:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<x-confirm-dialog />

@stack('scripts')
</body>
</html>
