@props([
    'name' => 'lampiran',
    'label' => 'Lampiran',
    'hint' => null,
    'max' => \App\Services\LampiranService::MAX_FILES,
    'maxKb' => \App\Services\LampiranService::MAX_KB,
    'existing' => [],
    'jenis' => null,
    'existingId' => null,
    'deleteName' => 'hapus_lampiran',
    'wajib' => false,
    'bare' => false,
])

@php
    $berkasLama = collect($existing)->filter(fn ($file) => is_array($file) && ! empty($file['path']))->values();
    $formatUkuran = function (int|float|null $bytes): string {
        $bytes = (int) $bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.').' KB';
        }
        return $bytes > 0 ? $bytes.' B' : '—';
    };
    $tipeGambar = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $accept = \App\Services\LampiranService::accept();
@endphp

<div class="{{ $bare ? 'mt-4' : 'card card-pad' }}" data-file-upload
     data-max="{{ $max }}"
     data-max-bytes="{{ $maxKb * 1024 }}"
     data-extensions="{{ implode(',', \App\Services\LampiranService::EKSTENSI_DIIZINKAN) }}"
     data-existing="{{ $berkasLama->count() }}">

    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h2 class="section-title">{{ $label }}@unless ($wajib) <span class="text-sm font-normal text-slate-500 dark:text-slate-400">(opsional)</span>@endunless</h2>
            <p class="hint mt-1">{{ $hint ?? 'Nota, struk, atau bukti pendukung lainnya.' }}</p>
        </div>
        <span class="badge badge-gray" data-counter>{{ $berkasLama->count() }}/{{ $max }} berkas</span>
    </div>

    @if ($berkasLama->isNotEmpty())
        <div class="mt-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Berkas tersimpan</p>
            <ul class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach ($berkasLama as $file)
                    @php
                        $path = (string) $file['path'];
                        $nama = (string) ($file['nama_asli'] ?? basename($path));
                        $mime = (string) ($file['mime'] ?? '');
                        $ext = strtoupper(pathinfo($nama, PATHINFO_EXTENSION) ?: pathinfo($path, PATHINFO_EXTENSION));
                        $ukuran = $formatUkuran($file['ukuran'] ?? 0);
                        $bisaPratinjau = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $tipeGambar, true);
                        $urlPratinjau = $jenis && $existingId
                            ? route('lampiran.download', ['jenis' => $jenis, 'id' => $existingId, 'index' => $loop->index, 'inline' => 1])
                            : null;
                        $urlUnduh = $jenis && $existingId
                            ? route('lampiran.download', ['jenis' => $jenis, 'id' => $existingId, 'index' => $loop->index])
                            : null;
                    @endphp
                    <li class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white p-2 transition dark:border-slate-700 dark:bg-slate-900/50" data-existing-item>
                        @if ($bisaPratinjau && $urlPratinjau)
                            <a href="{{ $urlPratinjau }}" target="_blank" rel="noopener"
                               class="group relative h-11 w-11 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">
                                <img src="{{ $urlPratinjau }}" alt="{{ $nama }}" loading="lazy"
                                     class="h-full w-full object-cover transition group-hover:scale-105">
                            </a>
                        @else
                            <span class="flex h-11 w-11 shrink-0 flex-col items-center justify-center gap-0.5 rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                <x-icon name="file-text" class="h-4 w-4" />
                                <span class="text-[9px] font-bold leading-none">{{ \Illuminate\Support\Str::limit($ext ?: 'FILE', 4, '') }}</span>
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            @if ($urlPratinjau && $bisaPratinjau)
                                <a href="{{ $urlPratinjau }}" target="_blank" rel="noopener" class="block truncate text-sm font-medium text-slate-800 hover:text-emerald-700 hover:underline dark:text-slate-100 dark:hover:text-emerald-400" data-nama-berkas>{{ $nama }}</a>
                            @else
                                <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-100" data-nama-berkas>{{ $nama }}</p>
                            @endif
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ $ukuran }}@if ($ext) · {{ $ext }} @endif</p>
                        </div>

                        @if ($urlUnduh)
                            <a href="{{ $urlUnduh }}" class="rounded-lg p-1.5 text-slate-500 dark:text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200" title="Unduh {{ $nama }}" aria-label="Unduh {{ $nama }}">
                                <x-icon name="download" class="h-4 w-4" />
                            </a>
                        @endif

                        <label class="shrink-0 cursor-pointer select-none">
                            <input type="checkbox" name="{{ $deleteName }}[]" value="{{ $path }}" class="peer sr-only" data-hapus-toggle>
                            <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 peer-checked:hidden dark:border-slate-700 dark:text-slate-400 dark:hover:border-red-500/40 dark:hover:bg-red-500/10">
                                <x-icon name="trash" class="h-3 w-3" /> Hapus
                            </span>
                            <span class="hidden items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-600 peer-checked:inline-flex dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-300">
                                <x-icon name="refresh" class="h-3 w-3" /> Batal
                            </span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-4 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/70 px-4 py-6 text-center transition hover:border-emerald-400 hover:bg-emerald-50/40 dark:border-slate-700 dark:bg-slate-900/40 dark:hover:border-emerald-500/60 dark:hover:bg-emerald-500/5"
         data-dropzone>
        <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-emerald-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:text-emerald-400 dark:ring-slate-700">
            <x-icon name="upload-cloud" class="h-5 w-5" />
        </span>
        <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Tarik &amp; lepas berkas di sini</p>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">atau pilih dari perangkat · maks {{ \App\Services\LampiranService::labelUkuran() }} per berkas</p>

        <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
            <button type="button" class="btn-primary btn-sm" data-browse>
                <x-icon name="paperclip" class="h-3.5 w-3.5" /> Pilih berkas
            </button>
            <button type="button" class="btn-secondary btn-sm" data-camera-open>
                <x-icon name="camera" class="h-3.5 w-3.5" /> Kamera
            </button>
        </div>

        <p class="mt-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">JPG · PNG · WEBP · HEIC · PDF · DOCX · XLSX</p>
    </div>

    <input type="file" name="{{ $name }}[]" multiple accept="{{ $accept }}" class="sr-only" data-input tabindex="-1" aria-hidden="true">
    {{-- Input kamera sengaja tanpa name: berkasnya disalin JS ke input utama. --}}
    <input type="file" accept="image/*" capture="environment" class="sr-only" data-camera tabindex="-1" aria-hidden="true">

    <div class="mt-3 hidden" data-terpilih>
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Akan diunggah</p>
        <ul class="mt-2 grid gap-2 sm:grid-cols-2" data-list></ul>
    </div>

    <p class="error-text hidden" role="alert" data-client-error></p>

    @foreach ($errors->get($name) as $pesan)
        <p class="error-text">{{ $pesan }}</p>
    @endforeach
    @foreach ($errors->get($name.'.*') as $daftarPesan)
        @foreach ((array) $daftarPesan as $pesan)
            <p class="error-text">{{ $pesan }}</p>
        @endforeach
    @endforeach
</div>
