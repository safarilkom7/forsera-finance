{{-- Dialog konfirmasi global. Dipanggil lewat window.konfirmasi() atau atribut data-confirm. --}}
<dialog id="confirm-dialog"
        aria-labelledby="confirm-dialog-title"
        aria-describedby="confirm-dialog-message"
        class="w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-[2px] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">

    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5 dark:border-slate-800">
        <h2 id="confirm-dialog-title" class="text-base font-bold text-slate-900 dark:text-white" data-confirm-title>Konfirmasi</h2>
        <button type="button" data-confirm-close aria-label="Tutup dialog"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>

    <div class="flex items-start gap-3.5 px-5 py-5">
        <span data-confirm-icon
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30">
            <x-icon name="warning" data-confirm-icon-svg="warning" class="h-5 w-5" />
            <x-icon name="alert-circle" data-confirm-icon-svg="danger" class="hidden h-5 w-5" />
            <x-icon name="info" data-confirm-icon-svg="info" class="hidden h-5 w-5" />
        </span>
        <p id="confirm-dialog-message" data-confirm-message class="max-h-56 overflow-y-auto pt-1 pr-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300"></p>
    </div>

    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3.5 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900/40">
        <button type="button" data-confirm-cancel class="btn-secondary">Batal</button>
        <button type="button" data-confirm-accept class="btn-primary" data-confirm-accept-label>Ya, lanjutkan</button>
    </div>
</dialog>
