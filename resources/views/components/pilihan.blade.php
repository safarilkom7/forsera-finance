{{--
    Dropdown pengganti <select> dengan tampilan seragam.

    Pemakaian: <x-pilihan name="unit" id="unit" searchable> ...option... </x-pilihan>
    Atribut lain (required, data-auto-submit, class) diteruskan ke <select> asli.
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
@endphp

<div class="relative" data-pilihan>
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
            <div class="border-b border-slate-100 p-2 dark:border-slate-800" @if (! $searchable) hidden @endif data-pilihan-cari-wrap>
                <input type="text" class="input py-1.5 text-sm" placeholder="Cari…" aria-label="Cari pilihan"
                       autocomplete="off" data-pilihan-cari>
            </div>
            <ul id="{{ $idDaftar }}" role="listbox" class="max-h-60 overflow-y-auto py-1" data-pilihan-daftar></ul>
        </div>
    </div>
</div>
