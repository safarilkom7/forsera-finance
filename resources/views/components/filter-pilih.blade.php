{{--
    Sel filter dropdown untuk baris filter tabel master.

    Sengaja memakai <select> native: daftar opsi pendek, tidak terpotong oleh
    wadah tabel yang bisa digeser horizontal, dan tetap berfungsi tanpa JS.
    Opsi pertama selalu "Semua …" (nilai kosong) sebagai reset kolom.
--}}
@props([
    'name',
    'value' => '',
    'options' => [],
    'placeholder' => 'Semua',
    'form' => null,
])

@php
    $id = $attributes->get('id', 'filter-'.$name);
    $atribut = $attributes->except(['id']);
    $kata = trim(preg_replace('/^semua\s+/i', '', $placeholder) ?? $placeholder);
@endphp

<th {{ $atribut->merge(['class' => 'font-normal']) }}>
    <select id="{{ $id }}" name="{{ $name }}" @if ($form) form="{{ $form }}" @endif
            class="input min-w-36 py-1.5 pr-6 text-[11px] font-normal normal-case tracking-normal"
            aria-label="Filter {{ $kata }}" data-auto-submit>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $nilai => $label)
            <option value="{{ $nilai }}" @selected((string) $value === (string) $nilai)>{{ $label }}</option>
        @endforeach
    </select>
</th>
