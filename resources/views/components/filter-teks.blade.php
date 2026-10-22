{{--
    Sel filter teks untuk baris filter tabel master.

    Input dikaitkan ke form GET di luar tabel lewat atribut `form`, karena form
    tidak boleh membungkus <table> yang di dalamnya sudah ada form hapus (POST).
    Tekan Enter untuk menyaring; tombol X mengosongkan kolom ini.
--}}
@props([
    'name',
    'value' => '',
    'placeholder' => 'Cari…',
    'form' => null,
    'ringkas' => false,
])

@php
    $id = $attributes->get('id', 'filter-'.$name);
    $atribut = $attributes->except(['id']);
    $kata = trim(preg_replace('/^cari\s+/i', '', $placeholder) ?? $placeholder);
@endphp

<th {{ $atribut->merge(['class' => 'font-normal']) }}>
    <div class="relative">
        <input id="{{ $id }}" type="text" name="{{ $name }}" value="{{ $value }}"
               @if ($form) form="{{ $form }}" @endif
               class="input {{ $ringkas ? 'min-w-24' : 'min-w-32' }} py-1.5 pl-2.5 pr-8 text-xs font-normal normal-case tracking-normal"
               placeholder="{{ $placeholder }}" aria-label="Filter {{ $kata }}"
               autocomplete="off" spellcheck="false" data-filter-input>
        <button type="button" @if ($value === '') hidden @endif
                class="absolute right-1 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 transition hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-slate-500 dark:hover:text-slate-300"
                data-filter-bersih="{{ $id }}" aria-label="Hapus filter {{ $kata }}">
            <x-icon name="x" class="h-3 w-3" />
        </button>
    </div>
</th>
