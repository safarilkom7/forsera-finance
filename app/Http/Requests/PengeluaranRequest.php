<?php

namespace App\Http\Requests;

use App\Models\MetodePembayaran;
use App\Models\UnitUsaha;
use App\Services\PengaturanService;
use Illuminate\Foundation\Http\FormRequest;

class PengeluaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $files = $this->file('lampiran', []);
        $files = is_array($files) ? array_values(array_filter($files)) : [];
        $this->merge(['lampiran' => $files === [] ? null : $files]);

        $detail = collect($this->input('detail', []))
            ->filter(fn ($baris) => is_array($baris) && ! empty($baris['item_id']))
            ->map(function ($baris) {
                return [
                    'item_id' => $baris['item_id'] ?? null,
                    'jumlah' => str_replace(',', '.', (string) ($baris['jumlah'] ?? '0')),
                    'satuan_id' => $baris['satuan_id'] ?? null,
                    'harga_satuan' => parse_rupiah($baris['harga_satuan'] ?? 0),
                    'subtotal' => parse_rupiah($baris['subtotal'] ?? 0),
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'detail' => $detail,
            'total_nominal' => parse_rupiah($this->input('total_nominal', 0)),
        ]);

        // Biarkan service memakai default metode tunai bila field tidak dikirim.
        if ($this->has('dari_kas_harian')) {
            $this->merge(['dari_kas_harian' => $this->boolean('dari_kas_harian')]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'unit_usaha_id' => ['required', 'integer', 'exists:unit_usaha,id'],
            'jenis_pengeluaran_id' => ['required', 'integer', 'exists:jenis_pengeluaran,id'],
            'metode_pembayaran_id' => ['required', 'integer', 'exists:metode_pembayaran,id'],
            'total_nominal' => ['nullable', 'integer', 'min:0'],
            'dari_kas_harian' => ['boolean'],
            'note' => ['nullable', 'string', 'max:500'],
            'detail' => ['array', 'max:100'],
            'detail.*.item_id' => ['required', 'integer', 'exists:item,id'],
            'detail.*.jumlah' => ['required', 'numeric', 'min:0.001'],
            'detail.*.satuan_id' => ['required', 'integer', 'exists:satuan,id'],
            'detail.*.harga_satuan' => ['required', 'integer', 'min:0'],
            'lampiran' => ['nullable', 'array', 'max:5'],
            'lampiran.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'hapus_lampiran' => ['nullable', 'array'],
            'hapus_lampiran.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal pengeluaran wajib diisi.',
            'unit_usaha_id.required' => 'Unit usaha wajib dipilih.',
            'jenis_pengeluaran_id.required' => 'Jenis pengeluaran wajib dipilih.',
            'metode_pembayaran_id.required' => 'Metode pembayaran wajib dipilih.',
            'detail.*.item_id.required' => 'Item wajib dipilih pada setiap baris detail.',
            'detail.*.jumlah.min' => 'Jumlah item harus lebih dari nol.',
            'detail.*.harga_satuan.min' => 'Harga satuan tidak boleh negatif.',
            'lampiran.max' => 'Maksimal 5 lampiran per transaksi.',
            'lampiran.*.mimes' => 'Lampiran harus berupa JPG, PNG, WEBP, atau PDF.',
            'lampiran.*.max' => 'Ukuran setiap lampiran maksimal 5 MB.',
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

                $detail = $this->input('detail', []);
                $total = $detail === []
                    ? (int) $this->input('total_nominal', 0)
                    : (int) array_sum(array_map(
                        fn ($baris) => (int) round((float) $baris['jumlah'] * (int) $baris['harga_satuan']),
                        $detail,
                    ));

                if ($total <= 0) {
                    $validator->errors()->add('total_nominal', 'Isi detail item atau total nominal pengeluaran.');
                }

                $maks = app(PengaturanService::class)->getInt('nominal_maks_transaksi', 2_000_000_000);
                if ($total > $maks) {
                    $validator->errors()->add('total_nominal', 'Total pengeluaran melebihi batas maksimum transaksi '.format_rupiah($maks).'.');
                }
            },
        ];
    }
}
