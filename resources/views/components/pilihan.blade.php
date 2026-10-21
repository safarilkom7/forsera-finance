{{--
    Dropdown pengganti <select> dengan tampilan seragam.

    Pemakaian: <x-pilihan name="unit" id="unit"> ...option... </x-pilihan>
    Atribut lain (required, data-auto-submit, class) diteruskan ke <select> asli.

    Kolom pencarian muncul otomatis saat pilihan cukup banyak (>= 8 opsi).
    Tambahkan atribut `searchable` untuk memaksanya selalu tampil.
    Tanpa JavaScript, <select> asli tetap tampil dan berfungsi seperti biasa.
--}}
@props([
    'name' => null,
    'id' => null,
    'searchable' => false,
])

@php
    $id = $id ?? $name ?? 'pilihan-'.uniqid();
    $kelas = $attributes->get('class', 'input');
    $atribut = $attributes->except(['class']);
    $idDaftar = $id.'-daftar';
    $idCari = $id.'-cari';
@endphp

<div class="relative" data-pilihan @if ($searchable) data-pilihan-cari-paksa @endif>
    <select @if ($name !== null) name="{{ $name }}" @endif
            id="{{ $id }}"
            class="{{ $kelas }}"
            data-pilihan-asli
            {{ $atribut }}>
        {{ $slot }}
    </select>

    <div hidden data-pilihan-kontrol>
        <div role="combobox" tabindex="0" aria-haspopup="listbox" aria-expanded="false"
             aria-controls="{{ $idDaftar }}"
             class="input flex w-full cursor-pointer items-center justify-between gap-2 text-left"
             data-pilihan-tombol>
            <span class="truncate" data-pilihan-label></span>
            <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-slate-500 transition-transform dark:text-slate-400" />
        </div>

        <div class="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
             hidden data-pilihan-menu>
            <div class="relative border-b border-slate-100 p-2 dark:border-slate-800"
                 @if (! $searchable) hidden @endif data-pilihan-cari-wrap>
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                <input type="text" id="{{ $idCari }}" class="input py-1.5 pl-9 pr-9 text-sm"
                       placeholder="Cari…" aria-label="Cari pilihan" aria-controls="{{ $idDaftar }}"
                       aria-autocomplete="list" autocomplete="off" spellcheck="false" data-pilihan-cari>
                <button type="button" hidden
                        class="absolute right-4 top-1/2 -translate-y-1/2 rounded p-0.5 text-slate-400 transition hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300"
                        aria-label="Bersihkan pencarian" data-pilihan-bersihkan>
                    <x-icon name="x" class="h-3.5 w-3.5" />
                </button>
            </div>
            <ul id="{{ $idDaftar }}" role="listbox" class="max-h-60 overflow-y-auto py-1" data-pilihan-daftar></ul>
            <div class="sr-only" aria-live="polite" role="status" data-pilihan-status></div>
        </div>
    </div>
</div>
