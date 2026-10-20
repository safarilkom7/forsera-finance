@props(['status', 'label' => null])

@php
    $nilai = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $teks = $label ?? ($status instanceof \BackedEnum && method_exists($status, 'getLabel') ? $status->getLabel() : str($nilai)->replace('_', ' ')->title());

    $gaya = match ($nilai) {
        'BELUM_DICEK' => 'badge-gray',
        'MENUNGGU_SETTLEMENT', 'MENUNGGU', 'INVESTIGASI', 'LEBIH' => 'badge-yellow',
        'SESUAI', 'DISETUJUI' => 'badge-green',
        'SELISIH', 'OPEN', 'DITOLAK', 'KURANG' => 'badge-red',
        'SELESAI' => 'badge-blue',
        'DIHAPUSKAN', 'DIBATALKAN' => 'badge-gray',
        default => 'badge-gray',
    };
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$gaya]) }}>{{ $teks }}</span>
