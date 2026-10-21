@extends('layouts.app')

@section('title', $pengeluaran ? 'Ubah Pengeluaran' : 'Pengeluaran Baru')
@section('subtitle', 'Detail item dihitung otomatis oleh sistem')

@section('content')
    @php
        $metodeTerpilih = (int) old('metode_pembayaran_id', $pengeluaran?->metode_pembayaran_id ?? $metodes->first()?->id);
        $unitTerpilih = (int) old('unit_usaha_id', $pengeluaran?->unit_usaha_id ?? $unitDefault);
        $jenisTerpilih = (int) old('jenis_pengeluaran_id', $pengeluaran?->jenis_pengeluaran_id ?? 0);
        $detailRows = old('detail');
        if (! is_array($detailRows) || $detailRows === []) {
            $detailRows = $pengeluaran?->detail?->map(fn ($d) => [
                'item_id' => $d->item_id,
                'jumlah' => $d->jumlah,
                'satuan_id' => $d->satuan_id,
                'harga_satuan' => $d->harga_satuan,
            ])->all() ?: [[]];
        }
    @endphp

    @if ($pengeluaran?->butuhPersetujuan())
        <div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            <x-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0" />
            <p>Pengeluaran ini menunggu persetujuan Owner. Perubahan hanya dapat dilakukan Owner.</p>
        </div>
    @endif

    <form method="POST" action="{{ $pengeluaran ? route('pengeluaran.update', $pengeluaran) : route('pengeluaran.store') }}" class="space-y-5" id="form-pengeluaran" enctype="multipart/form-data">
        @csrf
        @if ($pengeluaran)
            @method('PUT')
        @endif
        <input type="hidden" name="simpan_lagi" id="simpan_lagi" value="0">

        <div class="card card-pad">
            <h2 class="section-title mb-4">Informasi pengeluaran</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="tanggal" class="label">Tanggal</label>
                    <x-tanggal id="tanggal" name="tanggal" required value="{{ old('tanggal', $tanggalDefault) }}" class="input @error('tanggal') input-error @enderror" />
                    @error('tanggal')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="unit_usaha_id" class="label">Unit usaha</label>
                    <x-pilihan id="unit_usaha_id" name="unit_usaha_id" required class="input @error('unit_usaha_id') input-error @enderror">
                        <option value="">Pilih unit</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected($unitTerpilih === $unit->id)>{{ $unit->nama }}</option>
                        @endforeach
                    </x-pilihan>
                    @error('unit_usaha_id')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="jenis_pengeluaran_id" class="label">Jenis pengeluaran</label>
                    <x-pilihan id="jenis_pengeluaran_id" name="jenis_pengeluaran_id" required class="input @error('jenis_pengeluaran_id') input-error @enderror">
                        <option value="">Pilih jenis</option>
                        @foreach ($jenises as $jenis)
                            <option value="{{ $jenis->id }}" @selected($jenisTerpilih === $jenis->id)>{{ $jenis->nama }}</option>
                        @endforeach
                    </x-pilihan>
                    @error('jenis_pengeluaran_id')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="metode_pembayaran_id" class="label">Metode pembayaran</label>
                    <x-pilihan id="metode_pembayaran_id" name="metode_pembayaran_id" required class="input @error('metode_pembayaran_id') input-error @enderror">
                        <option value="">Pilih metode</option>
                        @foreach ($metodes as $metode)
                            <option value="{{ $metode->id }}" data-tipe="{{ $metode->tipe?->value }}" @selected($metodeTerpilih === $metode->id)>{{ $metode->nama }}</option>
                        @endforeach
                    </x-pilihan>
                    @error('metode_pembayaran_id')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="dari_kas_harian" class="label">Diambil dari kas harian?</label>
                    @php
                        $kasDefault = old('dari_kas_harian', $pengeluaran?->dari_kas_harian ?? ($metodes->firstWhere('id', $metodeTerpilih)?->tipe?->value === 'TUNAI' ? '1' : '0'));
                    @endphp
                    <x-pilihan id="dari_kas_harian" name="dari_kas_harian" class="input">
                        <option value="1" @selected((string) $kasDefault === '1')>Ya — uang tunai di laci</option>
                        <option value="0" @selected((string) $kasDefault === '0')>Tidak</option>
                    </x-pilihan>
                    <p class="hint">Untuk metode tunai default-nya Ya; dipakai saat rekonsiliasi kas.</p>
                </div>
                <div>
                    <label for="total_nominal" class="label">Total manual <span class="font-normal text-slate-500 dark:text-slate-400">(tanpa detail item)</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-500 dark:text-slate-400">Rp</span>
                        <input type="text" id="total_nominal" name="total_nominal" inputmode="numeric" data-rupiah value="{{ old('total_nominal', $pengeluaran?->total_nominal ?? '') }}" class="input pl-10" placeholder="0">
                    </div>
                    @error('total_nominal')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="note" class="label">Catatan <span class="font-normal text-slate-500 dark:text-slate-400">(opsional)</span></label>
                <textarea id="note" name="note" rows="2" maxlength="500" class="input @error('note') input-error @enderror" placeholder="Contoh: pembelian bahan baku mingguan">{{ old('note', $pengeluaran?->note) }}</textarea>
                @error('note')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="card card-pad">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="section-title">Detail item</h2>
                    <p class="hint mt-1">Subtotal dihitung dari jumlah × harga satuan. Kosongkan bila memakai total manual.</p>
                </div>
                <button type="button" id="add-detail" class="btn-secondary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Tambah item</button>
            </div>

            <div id="detail-rows" class="mt-4 space-y-3">
                @foreach ($detailRows as $i => $row)
                    <div class="detail-row rounded-2xl border border-slate-200 p-3 dark:border-slate-700" data-row>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-12">
                            <div class="col-span-2 sm:col-span-4">
                                <label class="label">Item</label>
                                <x-pilihan name="detail[{{ $i }}][item_id]" data-item searchable class="input">
                                    <option value="">Pilih item</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}" data-satuan="{{ $item->satuan_default_id }}" data-harga="{{ $item->harga_terakhir }}" @selected((int) ($row['item_id'] ?? 0) === $item->id)>{{ $item->nama }}</option>
                                    @endforeach
                                </x-pilihan>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Jumlah</label>
                                <input type="number" name="detail[{{ $i }}][jumlah]" value="{{ $row['jumlah'] ?? 1 }}" step="0.001" min="0.001" data-jumlah class="input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Satuan</label>
                                <x-pilihan name="detail[{{ $i }}][satuan_id]" data-satuan searchable class="input">
                                    <option value="">Pilih</option>
                                    @foreach ($satuans as $satuan)
                                        <option value="{{ $satuan->id }}" @selected((int) ($row['satuan_id'] ?? 0) === $satuan->id)>{{ $satuan->singkatan }} — {{ $satuan->nama }}</option>
                                    @endforeach
                                </x-pilihan>
                            </div>
                            <div class="sm:col-span-3">
                                <label class="label">Harga satuan</label>
                                <input type="text" name="detail[{{ $i }}][harga_satuan]" value="{{ $row['harga_satuan'] ?? '' }}" data-harga data-rupiah inputmode="numeric" class="input">
                            </div>
                            <div class="flex items-end justify-between gap-2 sm:col-span-1">
                                <div class="text-right">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Subtotal</p>
                                    <p class="text-sm font-semibold" data-subtotal>Rp 0</p>
                                </div>
                                <button type="button" class="rounded-lg p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10" data-remove aria-label="Hapus baris">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @error('detail')<p class="error-text">{{ $message }}</p>@enderror
            @error('detail.*')<p class="error-text">{{ $message }}</p>@enderror

            <div class="mt-4 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">Total detail item</span>
                <span class="text-lg font-bold text-slate-900 dark:text-white" id="total-detail">Rp 0</span>
            </div>
        </div>

        <x-file-upload
            name="lampiran"
            label="Lampiran"
            hint="Nota, kuitansi, atau bukti pembayaran. Foto langsung dari kamera didukung."
            jenis="pengeluaran"
            :existing-id="$pengeluaran?->id"
            :existing="$pengeluaran?->lampiran ?? []" />

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:flex-wrap">
            <a href="{{ route('pengeluaran.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ $pengeluaran ? 'Simpan perubahan' : 'Simpan pengeluaran' }}</button>
            @unless ($pengeluaran)
                <button type="submit" class="btn-secondary" onclick="document.getElementById('simpan_lagi').value = 1;"><x-icon name="plus" class="h-4 w-4" /> Simpan dan tambah lagi</button>
            @endunless
        </div>
    </form>

    <template id="detail-template">
        <div class="detail-row rounded-2xl border border-slate-200 p-3 dark:border-slate-700" data-row>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-12">
                <div class="col-span-2 sm:col-span-4">
                    <label class="label">Item</label>
                    <x-pilihan name="detail[__INDEX__][item_id]" data-item searchable class="input">
                        <option value="">Pilih item</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" data-satuan="{{ $item->satuan_default_id }}" data-harga="{{ $item->harga_terakhir }}">{{ $item->nama }}</option>
                        @endforeach
                    </x-pilihan>
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Jumlah</label>
                    <input type="number" name="detail[__INDEX__][jumlah]" value="1" step="0.001" min="0.001" data-jumlah class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Satuan</label>
                    <x-pilihan name="detail[__INDEX__][satuan_id]" data-satuan searchable class="input">
                        <option value="">Pilih</option>
                        @foreach ($satuans as $satuan)
                            <option value="{{ $satuan->id }}">{{ $satuan->singkatan }} — {{ $satuan->nama }}</option>
                        @endforeach
                    </x-pilihan>
                </div>
                <div class="sm:col-span-3">
                    <label class="label">Harga satuan</label>
                    <input type="text" name="detail[__INDEX__][harga_satuan]" data-harga inputmode="numeric" class="input">
                </div>
                <div class="flex items-end justify-between gap-2 sm:col-span-1">
                    <div class="text-right">
                        <p class="text-[10px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Subtotal</p>
                        <p class="text-sm font-semibold" data-subtotal>Rp 0</p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10" data-remove aria-label="Hapus baris"><x-icon name="trash" class="h-4 w-4" /></button>
                </div>
            </div>
        </div>
    </template>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const rows = document.getElementById('detail-rows');
                const template = document.getElementById('detail-template');
                const totalDetail = document.getElementById('total-detail');
                let nextIndex = {{ count($detailRows) }};

                const rupiah = (value) => {
                    const digits = String(value ?? '').replace(/\D/g, '');
                    return digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
                };
                const angka = (value) => parseInt(String(value ?? '').replace(/\D/g, ''), 10) || 0;

                const hitung = () => {
                    let total = 0;
                    rows.querySelectorAll('[data-row]').forEach((row) => {
                        const jumlah = parseFloat(row.querySelector('[data-jumlah]')?.value || '0') || 0;
                        const harga = angka(row.querySelector('input[data-harga]')?.value);
                        const subtotal = Math.round(jumlah * harga);
                        total += subtotal;
                        row.querySelector('[data-subtotal]').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
                    });
                    totalDetail.textContent = 'Rp ' + total.toLocaleString('id-ID');
                };

                const bind = (row) => {
                    const item = row.querySelector('[data-item]');
                    const harga = row.querySelector('input[data-harga]');
                    const jumlah = row.querySelector('[data-jumlah]');
                    const satuan = row.querySelector('[data-satuan].input') || row.querySelector('select[data-satuan]');

                    item?.addEventListener('change', () => {
                        const option = item.selectedOptions[0];
                        if (option?.dataset.satuan) {
                            const satuanRow = row.querySelector('select[data-satuan]');
                            satuanRow.value = option.dataset.satuan;
                            // Komponen dropdown membaca nilai dari select asli lewat event change.
                            satuanRow.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (option?.dataset.harga) {
                            harga.value = rupiah(option.dataset.harga);
                        }
                        hitung();
                    });

                    harga.addEventListener('input', () => {
                        harga.value = rupiah(harga.value);
                        hitung();
                    });

                    jumlah.addEventListener('input', hitung);

                    row.querySelector('[data-remove]')?.addEventListener('click', () => {
                        if (rows.querySelectorAll('[data-row]').length > 1) {
                            row.remove();
                        } else {
                            row.querySelectorAll('input, select').forEach((el) => { if (el.dataset.item !== undefined) el.value = ''; });
                        }
                        hitung();
                    });
                };

                rows.querySelectorAll('[data-row]').forEach(bind);
                hitung();

                document.getElementById('add-detail')?.addEventListener('click', () => {
                    const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
                    rows.insertAdjacentHTML('beforeend', html);
                    window.siapkanKomponen?.(rows.lastElementChild);
                    bind(rows.lastElementChild);
                    rows.lastElementChild.querySelector('[data-item]')?.focus();
                });

                document.getElementById('metode_pembayaran_id')?.addEventListener('change', (event) => {
                    const tipe = event.target.selectedOptions[0]?.dataset.tipe;
                    const kas = document.getElementById('dari_kas_harian');
                    if (kas) kas.value = tipe === 'TUNAI' ? '1' : '0';
                });
            });
        </script>
    @endpush
@endsection
