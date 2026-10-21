<?php

namespace App\Http\Requests;

use App\Models\KategoriPemasukan;
use App\Models\MetodePembayaran;
use App\Models\UnitUsaha;
use App\Services\LampiranService;
use App\Services\PengaturanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PemasukanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', \App\Models\Pemasukan::class) || $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $files = $this->file('lampiran', []);
        $files = LampiranService::bersihkan(is_array($files) ? $files : []);
        $this->merge(['lampiran' => $files === [] ? null : $files]);

        if ($this->has('nominal')) {
            $this->merge(['nominal' => parse_rupiah($this->input('nominal'))]);
        }
    }

    /**
     * Pastikan entri berkas kosong (mis. input yang dikosongkan pengguna)
     * tidak ikut divalidasi sebagai unggahan gagal.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'lampiran' => LampiranService::bersihkan((array) $this->file('lampiran', [])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'unit_usaha_id' => ['required', 'integer', 'exists:unit_usaha,id'],
            'kategori_pemasukan_id' => ['required', 'integer', 'exists:kategori_pemasukan,id'],
            'metode_pembayaran_id' => ['required', 'integer', 'exists:metode_pembayaran,id'],
            'nominal' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
            'lampiran' => ['nullable', 'array', 'max:'.LampiranService::MAX_FILES],
            'lampiran.*' => LampiranService::aturan(),
            'hapus_lampiran' => ['nullable', 'array'],
            'hapus_lampiran.*' => ['string'],
            'alasan' => ['nullable', 'string', 'max:1000'],
            'simpan_lagi' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.before_or_equal' => 'Tanggal pemasukan tidak boleh di masa depan.',
            'tanggal.required' => 'Tanggal pemasukan wajib diisi.',
            'nominal.required' => 'Nominal pemasukan wajib diisi.',
            'nominal.min' => 'Nominal pemasukan harus lebih dari nol.',
            'unit_usaha_id.required' => 'Unit usaha wajib dipilih.',
            'kategori_pemasukan_id.required' => 'Kategori pemasukan wajib dipilih.',
            'metode_pembayaran_id.required' => 'Metode pembayaran wajib dipilih.',
            ...LampiranService::pesan('lampiran'),
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $user = $this->user();
                $unitId = (int) $this->input('unit_usaha_id');
                $unit = UnitUsaha::query()->find($unitId);

                if ($unit === null || ! $unit->aktif) {
                    $validator->errors()->add('unit_usaha_id', 'Unit usaha tidak aktif.');
                } elseif (! $user->bolehAksesUnit($unitId)) {
                    $validator->errors()->add('unit_usaha_id', 'Anda tidak memiliki akses ke unit usaha ini.');
                }

                $metode = MetodePembayaran::query()->find($this->input('metode_pembayaran_id'));
                if ($metode === null || ! $metode->aktif) {
                    $validator->errors()->add('metode_pembayaran_id', 'Metode pembayaran tidak aktif.');
                }

                $kategori = KategoriPemasukan::query()->find($this->input('kategori_pemasukan_id'));
                if ($kategori === null || ! $kategori->aktif) {
                    $validator->errors()->add('kategori_pemasukan_id', 'Kategori pemasukan tidak aktif.');
                } elseif ($kategori->unit_usaha_id !== null && (int) $kategori->unit_usaha_id !== $unitId) {
                    $validator->errors()->add('kategori_pemasukan_id', 'Kategori tersebut bukan milik unit usaha yang dipilih.');
                }

                $maks = app(PengaturanService::class)->getInt('nominal_maks_transaksi', 2_000_000_000);
                if ((int) $this->input('nominal') > $maks) {
                    $validator->errors()->add('nominal', 'Nominal melebihi batas maksimum transaksi '.format_rupiah($maks).'.');
                }

                $lampiranService = app(LampiranService::class);
                $files = $this->file('lampiran', []);
                $files = LampiranService::bersihkan(is_array($files) ? $files : []);

                foreach ($files as $index => $file) {
                    try {
                        $lampiranService->validasiFile($file);
                    } catch (ValidationException $exception) {
                        foreach ($exception->errors()['lampiran'] ?? [] as $pesan) {
                            $validator->errors()->add('lampiran.'.$index, $pesan);
                        }
                    }
                }

                $pemasukan = $this->route('pemasukan');
                if ($pemasukan !== null) {
                    $dihapus = (array) $this->input('hapus_lampiran', []);
                    $tersisa = collect($pemasukan->lampiran ?? [])
                        ->reject(fn ($item) => in_array($item['path'] ?? null, $dihapus, true))
                        ->count();

                    if ($tersisa + count($files) > LampiranService::MAX_FILES) {
                        $validator->errors()->add('lampiran', 'Total lampiran melebihi '.LampiranService::MAX_FILES.' berkas. Hapus beberapa lampiran lama terlebih dahulu.');
                    }
                }

                if ($this->route('pemasukan') !== null && $this->route('pemasukan')->terkunci()) {
                    if (! $user->isOwner()) {
                        $validator->errors()->add('alasan', 'Data terkunci hanya dapat dikoreksi Owner.');
                    } elseif (trim((string) $this->input('alasan')) === '') {
                        $validator->errors()->add('alasan', 'Alasan koreksi wajib diisi untuk data terkunci.');
                    }
                }
            },
        ];
    }
}
