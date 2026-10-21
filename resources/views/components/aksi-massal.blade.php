{{-- Bar aksi massal: form berdiri sendiri, checkbox di tabel terhubung lewat atribut form="…". --}}
@props([
    'aksi',
    'formId' => 'aksi-massal-form',
    'kata' => 'data',
    'judul' => 'Hapus data terpilih?',
    'pesan' => '{n} ',
])

<form method="POST" action="{{ $aksi }}" id="{{ $formId }}"
      data-pilih-bar
      data-confirm="Data terpilih akan dihapus permanen dan tercatat di audit log."
      data-confirm-template="{{ $pesan }}{{ $kata }} terpilih akan dihapus permanen dan tercatat di audit log."
      data-confirm-title="{{ $judul }}"
      data-confirm-severity="danger"
      data-confirm-label="Ya, hapus"
      {{ $attributes->merge(['class' => 'hidden border-b border-slate-100 bg-red-50/60 px-4 py-3 dark:border-slate-800 dark:bg-red-500/5']) }}>
    @csrf

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
            <span data-pilih-jumlah class="font-bold">0</span> {{ $kata }} dipilih
        </p>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-pilih-kosongkan class="btn-ghost btn-sm">Batalkan pilihan</button>
            <button type="submit" data-pilih-hapus class="btn-danger btn-sm" disabled>
                <x-icon name="trash" class="h-3.5 w-3.5" /> Hapus terpilih
            </button>
        </div>
    </div>
</form>
