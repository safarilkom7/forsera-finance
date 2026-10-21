<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Masuk') — {{ $branding['nama'] }}</title>
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
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="relative hidden overflow-hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-amber-500/10 blur-3xl"></div>

        <div class="relative flex items-center gap-3">
            @if ($branding['logo_url'])
                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['brand'] }}" class="h-11 w-11 rounded-2xl bg-white object-contain p-1">
            @else
                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-500 text-base font-black">{{ $branding['inisial'] }}</div>
            @endif
            <div>
                <p class="text-base font-bold">{{ $branding['brand'] }}</p>
                @if ($branding['title'] !== '')
                    <p class="text-xs text-slate-400">{{ $branding['title'] }}</p>
                @endif
            </div>
        </div>

        <div class="relative max-w-md">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-400">Satu dashboard, tiga usaha</p>
            <h2 class="mt-4 text-4xl font-bold leading-tight">Pastikan setiap rupiah yang dicatat benar-benar masuk.</h2>
            <p class="mt-5 text-sm leading-6 text-slate-400">Catat pemasukan dan pengeluaran, cocokkan setoran QRIS, transfer, dan tunai, lalu tindak lanjuti setiap selisih — dari HP maupun desktop.</p>
            <div class="mt-8 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-4">
                    <p class="text-xs text-slate-400">Resto</p>
                    <p class="mt-1 text-sm font-semibold">Forsera</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-4">
                    <p class="text-xs text-slate-400">Kopi</p>
                    <p class="mt-1 text-sm font-semibold">Arobi</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-4">
                    <p class="text-xs text-slate-400">Barbershop</p>
                    <p class="mt-1 text-sm font-semibold">Forsera</p>
                </div>
            </div>
        </div>

        <p class="relative text-xs text-slate-500">© {{ date('Y') }} {{ $branding['brand'] }}. Zona waktu Asia/Jakarta.</p>
    </div>

    <div class="flex items-center justify-center px-4 py-10 sm:px-8">
        <div class="w-full max-w-md">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                @if ($branding['logo_url'])
                    <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['brand'] }}" class="h-10 w-10 rounded-xl bg-white object-contain p-1 ring-1 ring-slate-200 dark:ring-slate-700">
                @else
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-600 text-sm font-black text-white">{{ $branding['inisial'] }}</div>
                @endif
                <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $branding['brand'] }}</p>
                    @if ($branding['title'] !== '')
                        <p class="text-xs text-slate-500">{{ $branding['title'] }}</p>
                    @endif
                </div>
            </div>

            @if (session('sukses'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                    {{ session('sukses') }}
                </div>
            @endif
            @if (session('gagal'))
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                    {{ session('gagal') }}
                </div>
            @endif

            @yield('content')

            <div class="mt-8 flex items-center justify-between text-xs text-slate-400">
                <span>© {{ date('Y') }} {{ $branding['brand'] }}</span>
                <button type="button" class="rounded-lg p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800" data-dark-toggle aria-label="Ganti mode gelap">
                    <x-icon name="sun" class="h-4 w-4 dark:hidden" />
                    <x-icon name="moon" class="hidden h-4 w-4 dark:block" />
                </button>
            </div>
        </div>
    </div>
</div>

<x-confirm-dialog />
</body>
</html>
