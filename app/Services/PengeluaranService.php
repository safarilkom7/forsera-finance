<?php

namespace App\Services;

use App\Enums\StatusPersetujuanPengeluaran;
use App\Enums\TipeMetodePembayaran;
use App\Models\DetailPengeluaran;
use App\Models\Item;
use App\Models\MetodePembayaran;
use App\Models\Pengeluaran;
use App\Models\Satuan;
use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PengeluaranService
{
    public function __construct(
        private readonly PengaturanService $pengaturan,
        private readonly PeriodeService $periode,
        private readonly LampiranService $lampiran,
    ) {}

    public function buat(array $data, User $user): Pengeluaran
    {
        $data = $this->normalisasi($data);
        $this->validasi($data, $user);
        $this->periode->pastikanTerbuka($data['tanggal'], (int) $data['unit_usaha_id']);

        return DB::transaction(function () use ($data, $user) {
            $detail = $this->siapkanDetail($data['detail'] ?? []);
            $total = $this->hitungTotal($detail, $data['total_nominal'] ?? null);
            $status = $this->tentukanStatus($total, $user);

            $lampiran = $this->lampiranDariData($data, $user);

            $pengeluaran = Pengeluaran::query()->create([
                'tanggal' => $data['tanggal'],
                'unit_usaha_id' => $data['unit_usaha_id'],
                'jenis_pengeluaran_id' => $data['jenis_pengeluaran_id'],
                'metode_pembayaran_id' => $data['metode_pembayaran_id'],
                'total_nominal' => $total,
                'dari_kas_harian' => $data['dari_kas_harian'] ?? $this->defaultKasHarian((int) $data['metode_pembayaran_id']),
                'note' => $data['note'] ?? null,
                'lampiran' => $lampiran,
                'status_persetujuan' => $status,
                'disetujui_oleh' => $status === StatusPersetujuanPengeluaran::DISETUJUI ? $user->id : null,
                'disetujui_at' => $status === StatusPersetujuanPengeluaran::DISETUJUI ? now() : null,
                'dibuat_oleh' => $user->id,
            ]);

            $this->simpanDetail($pengeluaran, $detail);
            $user->update(['last_unit_usaha_id' => $pengeluaran->unit_usaha_id]);

            return $pengeluaran->load('detail');
        });
    }

    public function ubah(Pengeluaran $pengeluaran, array $data, User $user): Pengeluaran
    {
        if ($pengeluaran->rekonsiliasi_id !== null) {
            throw ValidationException::withMessages([
                'pengeluaran' => 'Pengeluaran ini sudah dipakai dalam rekonsiliasi. Batalkan rekonsiliasi terlebih dahulu.',
            ]);
        }

        if (! $pengeluaran->bisaDiubahOleh($user)) {
            throw ValidationException::withMessages(['pengeluaran' => 'Anda tidak berhak mengubah pengeluaran ini.']);
        }

        $data = $this->normalisasi($data);
        $this->validasi($data, $user);
        $this->periode->pastikanTerbuka($data['tanggal'], (int) $data['unit_usaha_id']);
        $this->periode->pastikanTerbuka($pengeluaran->tanggal, $pengeluaran->unit_usaha_id);

        return DB::transaction(function () use ($pengeluaran, $data, $user) {
            $detail = $this->siapkanDetail($data['detail'] ?? []);
            $total = $this->hitungTotal($detail, $data['total_nominal'] ?? null);
            $status = $this->tentukanStatus($total, $user);

            $lampiran = $pengeluaran->lampiran ?? [];
            if (! empty($data['lampiran_diubah'])) {
                $baru = $this->lampiranDariData($data, $user);
                $this->lampiran->hapusYangTidakDipakai($lampiran, $baru);
                $lampiran = $baru;
            }

            $pengeluaran->update([
                'tanggal' => $data['tanggal'],
                'unit_usaha_id' => $data['unit_usaha_id'],
                'jenis_pengeluaran_id' => $data['jenis_pengeluaran_id'],
                'metode_pembayaran_id' => $data['metode_pembayaran_id'],
                'total_nominal' => $total,
                'dari_kas_harian' => $data['dari_kas_harian'] ?? $this->defaultKasHarian((int) $data['metode_pembayaran_id']),
                'note' => $data['note'] ?? null,
                'lampiran' => $lampiran,
                'status_persetujuan' => $status,
                'disetujui_oleh' => $status === StatusPersetujuanPengeluaran::DISETUJUI ? $user->id : null,
                'disetujui_at' => $status === StatusPersetujuanPengeluaran::DISETUJUI ? now() : null,
                'diubah_oleh' => $user->id,
            ]);

            $pengeluaran->detail()->delete();
            $this->simpanDetail($pengeluaran, $detail);

            return $pengeluaran->refresh()->load('detail');
        });
    }

    public function hapus(Pengeluaran $pengeluaran, User $user): void
    {
        if ($pengeluaran->rekonsiliasi_id !== null) {
            throw ValidationException::withMessages([
                'pengeluaran' => 'Pengeluaran yang sudah dipakai dalam rekonsiliasi tidak dapat dihapus.',
            ]);
        }

        if (! $pengeluaran->bisaDiubahOleh($user)) {
            throw ValidationException::withMessages(['pengeluaran' => 'Anda tidak berhak menghapus pengeluaran ini.']);
        }

        $this->periode->pastikanTerbuka($pengeluaran->tanggal, $pengeluaran->unit_usaha_id);

        DB::transaction(function () use ($pengeluaran, $user) {
            activity('pengeluaran')
                ->performedOn($pengeluaran)
                ->causedBy($user)
                ->withProperties(['lama' => $pengeluaran->toArray()])
                ->log('Menghapus pengeluaran');

            $pengeluaran->delete();
        });
    }

    public function setujui(Pengeluaran $pengeluaran, User $user): Pengeluaran
    {
        if (! $user->isOwner()) {
            throw ValidationException::withMessages(['pengeluaran' => 'Hanya Owner yang dapat menyetujui pengeluaran.']);
        }

        if (! $pengeluaran->butuhPersetujuan()) {
            throw ValidationException::withMessages(['pengeluaran' => 'Pengeluaran ini tidak menunggu persetujuan.']);
        }

        $pengeluaran->update([
            'status_persetujuan' => StatusPersetujuanPengeluaran::DISETUJUI,
            'disetujui_oleh' => $user->id,
            'disetujui_at' => now(),
        ]);

        activity('pengeluaran')->performedOn($pengeluaran)->causedBy($user)->log('Menyetujui pengeluaran');

        return $pengeluaran;
    }

    public function tolak(Pengeluaran $pengeluaran, User $user): Pengeluaran
    {
        if (! $user->isOwner()) {
            throw ValidationException::withMessages(['pengeluaran' => 'Hanya Owner yang dapat menolak pengeluaran.']);
        }

        if (! $pengeluaran->butuhPersetujuan()) {
            throw ValidationException::withMessages(['pengeluaran' => 'Pengeluaran ini tidak menunggu persetujuan.']);
        }

        $pengeluaran->update([
            'status_persetujuan' => StatusPersetujuanPengeluaran::DITOLAK,
            'disetujui_oleh' => $user->id,
            'disetujui_at' => now(),
        ]);

        activity('pengeluaran')->performedOn($pengeluaran)->causedBy($user)->log('Menolak pengeluaran');

        return $pengeluaran;
    }

    /**
     * Deteksi lonjakan harga dibanding rata-rata 5 pembelian terakhir item.
     *
     * @param  array<int, array<string, mixed>>  $detail
     * @return array<int, array{item: string, harga: int, rata_rata: int, persen: float}>
     */
    public function deteksiLonjakan(array $detail): array
    {
        $batas = $this->pengaturan->getInt('lonjakan_harga_persen', 20);
        $peringatan = [];

        foreach ($detail as $baris) {
            $item = Item::query()->find($baris['item_id'] ?? null);
            if ($item === null) {
                continue;
            }

            $rata = $item->rataRataLimaTerakhir();
            $harga = (int) parse_rupiah($baris['harga_satuan'] ?? 0);

            if ($rata !== null && $rata > 0 && $harga > $rata * (1 + $batas / 100)) {
                $peringatan[] = [
                    'item' => $item->nama,
                    'harga' => $harga,
                    'rata_rata' => $rata,
                    'persen' => round((($harga - $rata) / $rata) * 100, 1),
                ];
            }
        }

        return $peringatan;
    }

    private function normalisasi(array $data): array
    {
        $data['tanggal'] = isset($data['tanggal']) ? Carbon::parse($data['tanggal'])->toDateString() : now()->toDateString();
        $data['total_nominal'] = parse_rupiah($data['total_nominal'] ?? 0);

        if (isset($data['detail']) && is_array($data['detail'])) {
            $data['detail'] = array_values(array_filter($data['detail'], fn ($row) => is_array($row) && ! empty($row['item_id'])));
        } else {
            $data['detail'] = [];
        }

        return $data;
    }

    private function validasi(array $data, User $user): void
    {
        $validator = Validator::make($data, [
            'tanggal' => ['required', 'date'],
            'unit_usaha_id' => ['required', 'exists:unit_usaha,id'],
            'jenis_pengeluaran_id' => ['required', 'exists:jenis_pengeluaran,id'],
            'metode_pembayaran_id' => ['required', 'exists:metode_pembayaran,id'],
            'total_nominal' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'detail' => ['array'],
            'detail.*.item_id' => ['required', 'exists:item,id'],
            'detail.*.jumlah' => ['required', 'numeric', 'min:0.001'],
            'detail.*.harga_satuan' => ['required', 'integer', 'min:0'],
            'detail.*.satuan_id' => ['required', 'exists:satuan,id'],
        ], [
            'detail.*.item_id.required' => 'Item wajib dipilih pada setiap baris detail.',
            'detail.*.jumlah.min' => 'Jumlah item harus lebih dari nol.',
        ]);

        $validator->after(function ($validator) use ($data, $user) {
            $unit = UnitUsaha::query()->find($data['unit_usaha_id'] ?? null);

            if ($unit === null || ! $unit->aktif) {
                $validator->errors()->add('unit_usaha_id', 'Unit usaha tidak aktif.');
            } elseif (! $user->bolehAksesUnit($unit->id)) {
                $validator->errors()->add('unit_usaha_id', 'Anda tidak memiliki akses ke unit usaha ini.');
            }

            $metode = MetodePembayaran::query()->find($data['metode_pembayaran_id'] ?? null);
            if ($metode === null || ! $metode->aktif) {
                $validator->errors()->add('metode_pembayaran_id', 'Metode pembayaran tidak aktif.');
            }

            $detail = $data['detail'] ?? [];

            if ($detail === [] && parse_rupiah($data['total_nominal'] ?? 0) <= 0) {
                $validator->errors()->add('total_nominal', 'Isi detail item atau total nominal pengeluaran.');
            }

            foreach ($detail as $index => $baris) {
                $satuan = Satuan::query()->find($baris['satuan_id'] ?? null);
                if ($satuan === null) {
                    $validator->errors()->add("detail.$index.satuan_id", 'Satuan tidak valid.');
                }
            }

            $total = $detail === []
                ? parse_rupiah($data['total_nominal'] ?? 0)
                : (int) array_sum(array_map(
                    fn ($baris) => (int) round((float) $baris['jumlah'] * parse_rupiah($baris['harga_satuan'])),
                    $detail,
                ));

            $maks = $this->pengaturan->getInt('nominal_maks_transaksi', 2_000_000_000);
            if ($total > $maks) {
                $validator->errors()->add('total_nominal', 'Total pengeluaran melebihi batas maksimum transaksi '.format_rupiah($maks).'.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $detail
     * @return array<int, array<string, mixed>>
     */
    private function siapkanDetail(array $detail): array
    {
        return array_map(function ($baris) {
            $jumlah = (float) $baris['jumlah'];
            $harga = (int) parse_rupiah($baris['harga_satuan']);

            return [
                'item_id' => (int) $baris['item_id'],
                'jumlah' => round($jumlah, 3),
                'satuan_id' => (int) $baris['satuan_id'],
                'harga_satuan' => $harga,
                'subtotal' => (int) round($jumlah * $harga),
            ];
        }, $detail);
    }

    /**
     * @param  array<int, array<string, mixed>>  $detail
     */
    private function hitungTotal(array $detail, mixed $totalManual): int
    {
        if ($detail !== []) {
            return (int) array_sum(array_column($detail, 'subtotal'));
        }

        return (int) parse_rupiah($totalManual);
    }

    private function tentukanStatus(int $total, User $user): StatusPersetujuanPengeluaran
    {
        $batas = $this->pengaturan->getInt('batas_persetujuan_pengeluaran', 0);

        if ($batas > 0 && $total > $batas && $user->isStaf()) {
            return StatusPersetujuanPengeluaran::MENUNGGU;
        }

        return StatusPersetujuanPengeluaran::DISETUJUI;
    }

    private function defaultKasHarian(int $metodeId): bool
    {
        $metode = MetodePembayaran::query()->find($metodeId);

        return $metode?->tipe === TipeMetodePembayaran::TUNAI;
    }

    /**
     * @param  array<int, array<string, mixed>>  $detail
     */
    private function simpanDetail(Pengeluaran $pengeluaran, array $detail): void
    {
        foreach ($detail as $baris) {
            DetailPengeluaran::query()->create([
                'pengeluaran_id' => $pengeluaran->id,
                'item_id' => $baris['item_id'],
                'jumlah' => $baris['jumlah'],
                'satuan_id' => $baris['satuan_id'],
                'harga_satuan' => $baris['harga_satuan'],
                'subtotal' => $baris['subtotal'],
            ]);

            Item::query()->whereKey($baris['item_id'])->update(['harga_terakhir' => $baris['harga_satuan']]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lampiranDariData(array $data, User $user): array
    {
        if (isset($data['lampiran']) && is_array($data['lampiran'])) {
            return array_values(array_filter(
                $data['lampiran'],
                fn ($item) => is_array($item) && isset($item['path']),
            ));
        }

        $paths = $data['lampiran_files'] ?? [];

        if (! is_array($paths) || $paths === []) {
            return [];
        }

        return $this->lampiran->dariPaths($paths, $data['lampiran_nama_asli'] ?? [], $user);
    }
}
