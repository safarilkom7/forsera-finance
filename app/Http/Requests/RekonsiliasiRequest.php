<?php

namespace App\Http\Requests;

use App\Models\MetodePembayaran;
use App\Models\UnitUsaha;
use App\Services\LampiranService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RekonsiliasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() || $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $files = $this->file('bukti', []);
        $files = LampiranService::bersihkan(is_array($files) ? $files : []);
        $this->merge(['bukti' => $files === [] ? null : $files]);

        $this->merge([
            'total_diterima' => parse_rupiah($this->input('total_diterima', 0)),
            'biaya_admin' => parse_rupiah($this->input('biaya_admin', 0)),
            'potongan_kas_manual' => $this->filled('potongan_kas_manual') ? parse_rupiah($this->input('potongan_kas_manual')) : null,
            'menunggu_settlement' => $this->boolean('menunggu_settlement'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'bukti' => LampiranService::bersihkan((array) $this->file('bukti', [])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'unit_usaha_id' => ['required', 'integer', 'exists:unit_usaha,id'],
            'metode_pembayaran_id' => ['required', 'integer', 'exists:metode_pembayaran,id'],
            'periode_mulai' => ['required', 'date'],
            'periode_selesai' => ['required', 'date', 'after_or_equal:periode_mulai'],
            'pemasukan_ids' => ['required', 'array', 'min:1'],
            'pemasukan_ids.*' => ['integer', 'exists:pemasukan,id'],
            'tanggal_diterima' => ['nullable', 'date'],
            'total_diterima' => ['nullable', 'integer', 'min:0'],
            'biaya_admin' => ['nullable', 'integer', 'min:0'],
            'potongan_kas_manual' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'menunggu_settlement' => ['boolean'],
            'bukti' => ['nullable', 'array', 'max:'.LampiranService::MAX_FILES],
            'bukti.*' => LampiranService::aturan(),
        ];
    }

    public function messages(): array
    {
        return [
            'pemasukan_ids.required' => 'Pilih minimal satu pemasukan untuk direkonsiliasi.',
            'pemasukan_ids.min' => 'Pilih minimal satu pemasukan untuk direkonsiliasi.',
            'periode_selesai.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
            ...LampiranService::pesan('bukti'),
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $unit = UnitUsaha::query()->find($this->input('unit_usaha_id'));
                if ($unit === null || ! $unit->aktif) {
                    $validator->errors()->add('unit_usaha_id', 'Unit usaha tidak aktif.');
                } elseif (! $this->user()->bolehAksesUnit($unit->id)) {
                    $validator->errors()->add('unit_usaha_id', 'Anda tidak memiliki akses ke unit usaha ini.');
                }

                $metode = MetodePembayaran::query()->find($this->input('metode_pembayaran_id'));
                if ($metode === null || ! $metode->aktif) {
                    $validator->errors()->add('metode_pembayaran_id', 'Metode pembayaran tidak aktif.');
                }

                if (! $this->boolean('menunggu_settlement') && ! $this->filled('tanggal_diterima')) {
                    $validator->errors()->add('tanggal_diterima', 'Tanggal dana diterima wajib diisi.');
                }

                $mulai = Carbon::parse($this->input('periode_mulai'));
                $selesai = Carbon::parse($this->input('periode_selesai'));
                if ($mulai->diffInDays($selesai) > 366) {
                    $validator->errors()->add('periode_selesai', 'Rentang rekonsiliasi maksimal satu tahun.');
                }

                $lampiranService = app(LampiranService::class);
                $files = $this->file('bukti', []);
                $files = LampiranService::bersihkan(is_array($files) ? $files : []);

                foreach ($files as $index => $file) {
                    try {
                        $lampiranService->validasiFile($file);
                    } catch (ValidationException $exception) {
                        foreach ($exception->errors()['lampiran'] ?? [] as $pesan) {
                            $validator->errors()->add('bukti.'.$index, $pesan);
                        }
                    }
                }
            },
        ];
    }
}
