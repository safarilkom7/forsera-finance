@extends('layouts.app')

@section('title', $pemasukan ? ($terkunci ? 'Koreksi Pemasukan' : 'Ubah Pemasukan') : 'Pemasukan Baru')
@section('subtitle', $pemasukan ? $pemasukan->unitUsaha?->nama.' · '.format_tanggal($pemasukan->tanggal) : 'Catat uang masuk dengan cepat')

@section('content')
    @php
        $metodeTerpilih = (int) old('metode_pembayaran_id', $pemasukan?->metode_pembayaran_id ?? $metodes->first()?->id);
        $unitTerpilih = (int) old('unit_usaha_id', $pemasukan?->unit_usaha_id ?? $unitDefault);
        $kategoriTerpilih = (int) old('kategori_pemasukan_id', $pemasukan?->kategori_pemasukan_id ?? 0);
        $nominalNilai = old('nominal', $pemasukan?->nominal ?? '');
    @endphp

    @if ($terkunci)
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            <x-icon name="lock" class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-semibold">Data terkunci karena sudah diverifikasi</p>
                <p class="mt-0.5">Koreksi hanya dapat dilakukan Owner dan wajib menyertakan alasan. Rekonsiliasi terkait akan dihitung ulang otomatis.</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $pemasukan ? route('pemasukan.update', $pemasukan) : route('pemasukan.store') }}" class="space-y-5" id="form-pemasukan" enctype="multipart/form-data">
        @csrf
        @if ($pemasukan)
            @method('PUT')
        @endif
        <input type="hidden" name="simpan_lagi" id="simpan_lagi" value="0">

        <div class="card card-pad">
            <h2 class="section-title mb-4">Detail pemasukan</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="tanggal" class="label">Tanggal</label>
                    <x-tanggal id="tanggal" name="tanggal" required max="{{ now()->toDateString() }}"
                           value="{{ old('tanggal', $tanggalDefault) }}"
                           class="input @error('tanggal') input-error @enderror" />
                    @error('tanggal')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="unit_usaha_id" class="label">Unit usaha</label>
                    @if ($terkunci)
                        <input type="hidden" name="unit_usaha_id" value="{{ $unitTerpilih }}">
                        <p class="input bg-slate-50 dark:bg-slate-800">{{ $pemasukan->unitUsaha?->nama }}</p>
                    @else
                        <x-pilihan id="unit_usaha_id" name="unit_usaha_id" required class="input @error('unit_usaha_id') input-error @enderror">
                            <option value="">Pilih unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected($unitTerpilih === $unit->id)>{{ $unit->nama }}</option>
                            @endforeach
                        </x-pilihan>
                    @endif
                    @error('unit_usaha_id')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4">
                <span class="label">Metode pembayaran</span>
                @if ($terkunci)
                    <input type="hidden" name="metode_pembayaran_id" value="{{ $metodeTerpilih }}">
                    <p class="input bg-slate-50 dark:bg-slate-800">{{ $metodes->firstWhere('id', $metodeTerpilih)?->nama }}</p>
                @else
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($metodes as $metode)
                            <label class="cursor-pointer rounded-xl border border-slate-300 bg-white px-3 py-3 text-center text-sm font-semibold text-slate-600 transition hover:border-emerald-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700 dark:has-[:checked]:bg-emerald-500/10 dark:has-[:checked]:text-emerald-300">
                                <input type="radio" name="metode_pembayaran_id" value="{{ $metode->id }}" class="sr-only" @checked($metodeTerpilih === $metode->id)>
                                {{ $metode->nama }}
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('metode_pembayaran_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="kategori_pemasukan_id" class="label">Kategori</label>
                    <x-pilihan id="kategori_pemasukan_id" name="kategori_pemasukan_id" required searchable class="input @error('kategori_pemasukan_id') input-error @enderror">
                        <option value="">Pilih kategori</option>
                        @foreach ($kategoris as $kategori)
                            <option value="{{ $kategori->id }}" data-unit="{{ $kategori->unit_usaha_id ?? 'all' }}" @selected($kategoriTerpilih === $kategori->id)>
                                {{ $kategori->nama }}{{ $kategori->unit_usaha_id ? '' : ' (semua unit)' }}
                            </option>
                        @endforeach
                    </x-pilihan>
                    @error('kategori_pemasukan_id')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nominal" class="label">Nominal</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-500 dark:text-slate-400">Rp</span>
                        <input type="text" id="nominal" name="nominal" inputmode="numeric" required
                               value="{{ $nominalNilai }}" data-rupiah
                               class="input pl-10 text-lg font-bold @error('nominal') input-error @enderror"
                               placeholder="0">
                    </div>
                    @error('nominal')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="note" class="label">Catatan <span class="font-normal text-slate-500 dark:text-slate-400">(opsional)</span></label>
                <textarea id="note" name="note" rows="2" maxlength="500" class="input @error('note') input-error @enderror" placeholder="Contoh: meja 4, pesanan take away">{{ old('note', $pemasukan?->note) }}</textarea>
                @error('note')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            @if ($terkunci)
                <div class="mt-4">
                    <label for="alasan" class="label">Alasan koreksi <span class="text-red-600">*</span></label>
                    <textarea id="alasan" name="alasan" rows="2" required maxlength="1000" class="input @error('alasan') input-error @enderror" placeholder="Jelaskan alasan koreksi data terkunci">{{ old('alasan') }}</textarea>
                    @error('alasan')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            @endif
        </div>

        @unless ($terkunci)
            <x-file-upload
                name="lampiran"
                label="Lampiran"
                hint="Struk kasir, bukti transfer, atau rekap setoran."
                jenis="pemasukan"
                :existing-id="$pemasukan?->id"
                :existing="$pemasukan?->lampiran ?? []" />
        @endunless

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:flex-wrap">
            <a href="{{ route('pemasukan.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">
                <x-icon name="check" class="h-4 w-4" /> {{ $pemasukan ? 'Simpan perubahan' : 'Simpan pemasukan' }}
            </button>
            @unless ($pemasukan)
                <button type="submit" class="btn-secondary" onclick="document.getElementById('simpan_lagi').value = 1;">
                    <x-icon name="plus" class="h-4 w-4" /> Simpan dan tambah lagi
                </button>
            @endunless
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const unit = document.getElementById('unit_usaha_id');
                const kategori = document.getElementById('kategori_pemasukan_id');

                const syncKategori = () => {
                    if (!unit || !kategori) return;
                    const unitId = unit.value;
                    Array.from(kategori.options).forEach((option) => {
                        if (!option.value) return;
                        const dataUnit = option.dataset.unit;
                        option.hidden = dataUnit !== 'all' && dataUnit !== unitId;
                    });
                    if (kategori.selectedOptions[0]?.hidden) {
                        kategori.value = '';
                        // Beri tahu komponen dropdown agar label yang tampil ikut berubah.
                        kategori.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                };

                unit?.addEventListener('change', syncKategori);
                syncKategori();
            });
        </script>
    @endpush
@endsection
