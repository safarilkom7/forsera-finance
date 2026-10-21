@props([
    'name',
    'label',
    'autocomplete' => 'current-password',
    'hint' => null,
    'placeholder' => null,
    'required' => true,
])

<div>
    <label for="{{ $name }}" class="label">{{ $label }}</label>
    <div class="relative">
        <input id="{{ $name }}" name="{{ $name }}" type="password"
               autocomplete="{{ $autocomplete }}"
               @if ($required) required @endif
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif
               class="input pr-11 @error($name) input-error @enderror">

        <button type="button" data-password-toggle
                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-slate-400 transition hover:text-slate-600 focus:outline-none focus-visible:text-emerald-600 dark:hover:text-slate-200 dark:focus-visible:text-emerald-400"
                aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan password">
            <x-icon name="eye" class="h-5 w-5" data-password-icon="show" />
            <x-icon name="eye-off" class="hidden h-5 w-5" data-password-icon="hide" />
        </button>
    </div>
    @if ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="error-text">{{ $message }}</p>
    @enderror
</div>
