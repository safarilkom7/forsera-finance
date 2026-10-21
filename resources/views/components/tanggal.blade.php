{{--
    Input tanggal dengan kalender popup dan format Indonesia ("22 Okt 2026").

    Pemakaian: <x-tanggal name="tanggal" id="tanggal" :value="old('tanggal')" required />
    Atribut lain (required, min, max, class) diteruskan ke <input type="date"> asli,
    yang tetap tampil dan berfungsi bila JavaScript belum jalan.
--}}
@props([
    'name' => null,
    'id' => null,
    'value' => null,
])

@php
    $id = $id ?? $name ?? 'tanggal-'.uniqid();
    $kelas = $attributes->get('class', 'input');
    $atribut = $attributes->except(['class']);
    $nilai = $value === null ? '' : (string) $value;
    $teks = $nilai !== '' ? format_tanggal($nilai) : null;
    $idPopup = $id.'-popup';
@endphp

<div class="relative" data-kalender>
    <input type="date" @if ($name !== null) name="{{ $name }}" @endif
           id="{{ $id }}"
           value="{{ $nilai }}"
           class="{{ $kelas }}"
           data-kalender-asli
           {{ $atribut }}>

    <div hidden data-kalender-kontrol>
        <button type="button"
                class="input flex w-full items-center justify-between gap-2 text-left"
                aria-haspopup="dialog" aria-expanded="false" aria-controls="{{ $idPopup }}"
                data-kalender-tombol>
            <span class="truncate @if ($teks === null) text-slate-500 dark:text-slate-400 @endif"
                  data-kalender-label>{{ $teks ?? 'Pilih tanggal' }}</span>
            <x-icon name="calendar" class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" />
        </button>

        <div id="{{ $idPopup }}" role="dialog" aria-modal="false" aria-label="Pilih tanggal"
             hidden data-kalender-popup
             class="absolute z-30 mt-1 w-72 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white p-3 shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-2">
                <button type="button" data-kalender-mundur aria-label="Bulan sebelumnya"
                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-slate-300 dark:hover:bg-slate-800">
                    <x-icon name="chevron-right" class="h-4 w-4 rotate-180" />
                </button>
                <p class="text-sm font-semibold text-slate-900 dark:text-white" aria-live="polite" data-kalender-judul></p>
                <button type="button" data-kalender-maju aria-label="Bulan berikutnya"
                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 dark:text-slate-300 dark:hover:bg-slate-800">
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </button>
            </div>

            <div class="mt-2 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                 aria-hidden="true">
                <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span>
                <span>Jum</span><span>Sab</span><span>Min</span>
            </div>

            <div class="mt-1 grid grid-cols-7 gap-1" data-kalender-hari></div>

            <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2 dark:border-slate-800">
                <button type="button" class="btn-ghost btn-sm" data-kalender-hari-ini>Hari ini</button>
                <button type="button" class="btn-ghost btn-sm" data-kalender-kosong>Kosongkan</button>
            </div>
        </div>
    </div>
</div>
