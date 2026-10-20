@extends('layouts.app')

@section('title', 'Pengaturan')
@section('subtitle', 'Parameter bisnis yang dipakai sistem')

@section('content')
    <form method="POST" action="{{ route('pengaturan.update') }}" class="card card-pad max-w-4xl space-y-5">
        @csrf
        @method('PUT')

        @foreach ($defaults as $key => $meta)
            <div class="grid gap-2 border-b border-slate-100 pb-4 last:border-0 last:pb-0 dark:border-slate-800 sm:grid-cols-3 sm:gap-4">
                <div>
                    <label for="{{ $key }}" class="label">{{ str($key)->replace('_', ' ')->title() }}</label>
                    <p class="text-[11px] text-slate-400">{{ $key }}</p>
                </div>
                <div>
                    <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, $nilai[$key] ?? $meta['value']) }}" required
                           inputmode="{{ $meta['tipe'] === 'integer' ? 'numeric' : 'decimal' }}"
                           @isset($meta['min']) min="{{ $meta['min'] }}" @endisset
                           @isset($meta['max']) max="{{ $meta['max'] }}" @endisset
                           class="input @error($key) input-error @enderror">
                    @if (isset($meta['min']) || isset($meta['max']))
                        <p class="mt-1 text-[11px] text-slate-400">Rentang: {{ $meta['min'] ?? 0 }} – {{ $meta['max'] ?? '∞' }}</p>
                    @endif
                    @error($key)<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <p class="hint self-center">{{ $meta['deskripsi'] }}</p>
            </div>
        @endforeach

        <div class="flex gap-2">
            <button class="btn-primary">Simpan pengaturan</button>
            <a href="{{ route('dashboard') }}" class="btn-secondary">Kembali</a>
        </div>
    </form>
@endsection
