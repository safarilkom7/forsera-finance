<?php

namespace App\Services;

use App\Models\DetailPengeluaran;
use App\Models\Item;
use App\Models\JenisPengeluaran;
use App\Models\KategoriPemasukan;
use App\Models\MetodePembayaran;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Satuan;
use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Master data tidak memakai soft delete: transaksi historis menunjuk langsung ke
 * barisnya lewat foreign key. Karena itu penghapusan hanya boleh untuk data yang
 * belum dipakai transaksi aktif; sisanya cukup dinonaktifkan (kolom "aktif").
 * Transaksi yang sudah dihapus (soft delete) tidak menghalangi: baris lunaknya
 * dibersihkan permanen lebih dulu supaya foreign key tidak menolak.
 */
class MasterDataService
{
    /** @var array<string, array{model: class-string<Model>, label: string}> */
    public const JENIS = [
        'unit' => ['model' => UnitUsaha::class, 'label' => 'Unit usaha'],
        'metode' => ['model' => MetodePembayaran::class, 'label' => 'Metode pembayaran'],
        'kategori' => ['model' => KategoriPemasukan::class, 'label' => 'Kategori pemasukan'],
        'jenis' => ['model' => JenisPengeluaran::class, 'label' => 'Jenis pengeluaran'],
        'satuan' => ['model' => Satuan::class, 'label' => 'Satuan'],
        'item' => ['model' => Item::class, 'label' => 'Item'],
    ];

    /**
     * @return class-string<Model>
     */
    public function model(string $jenis): string
    {
        return $this->jenis($jenis)['model'];
    }

    public function label(string $jenis): string
    {
        return $this->jenis($jenis)['label'];
    }

    /**
     * @return array{model: class-string<Model>, label: string}
     */
    private function jenis(string $jenis): array
    {
        return self::JENIS[$jenis] ?? throw new NotFoundHttpException('Jenis data master tidak dikenal.');
    }

    /**
     * Sumber pemakaian setiap jenis master: nama sumber => [model, kolom foreign key].
     *
     * @return array<string, array{0: class-string<Model>, 1: string}>
     */
    private function sumberPemakaian(string $jenis): array
    {
        return match ($jenis) {
            'unit' => [
                'pemasukan' => [Pemasukan::class, 'unit_usaha_id'],
                'pengeluaran' => [Pengeluaran::class, 'unit_usaha_id'],
                'kategori' => [KategoriPemasukan::class, 'unit_usaha_id'],
            ],
            'metode' => [
                'pemasukan' => [Pemasukan::class, 'metode_pembayaran_id'],
                'pengeluaran' => [Pengeluaran::class, 'metode_pembayaran_id'],
            ],
            'kategori' => [
                'pemasukan' => [Pemasukan::class, 'kategori_pemasukan_id'],
            ],
            'jenis' => [
                'pengeluaran' => [Pengeluaran::class, 'jenis_pengeluaran_id'],
                'item' => [Item::class, 'jenis_pengeluaran_id'],
            ],
            'satuan' => [
                'item' => [Item::class, 'satuan_default_id'],
                'detail pengeluaran' => [DetailPengeluaran::class, 'satuan_id'],
            ],
            'item' => [
                'detail pengeluaran' => [DetailPengeluaran::class, 'item_id'],
            ],
        };
    }

    /**
     * Ringkasan pemakaian setiap baris, mis. "dipakai 2 pemasukan, 1 pengeluaran".
     * String kosong berarti baris aman dihapus. Transaksi yang sudah dihapus
     * (soft delete) tidak dihitung karena tidak terlihat di mana pun.
     *
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    public function keteranganPemakaian(string $jenis, array $ids): array
    {
        $this->jenis($jenis);

        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $keterangan = [];

        foreach ($this->sumberPemakaian($jenis) as $nama => [$kelas, $kolom]) {
            foreach ($this->hitung($kelas, $kolom, $ids) as $id => $jumlah) {
                $keterangan[$id][] = $jumlah.' '.$nama;
            }
        }

        $hasil = [];

        foreach ($ids as $id) {
            $bagian = $keterangan[$id] ?? [];
            $hasil[$id] = $bagian === [] ? '' : 'dipakai '.implode(', ', $bagian);
        }

        return $hasil;
    }

    /**
     * Hapus satu atau beberapa baris. Baris yang masih dipakai dilewati dan
     * dilaporkan alasannya, bukan menggagalkan seluruh permintaan.
     *
     * @param  array<int, int>  $ids
     * @return array{terhapus: int, dilewati: array<int, string>}
     */
    public function hapusBanyak(string $jenis, array $ids, User $user): array
    {
        $model = $this->model($jenis);

        $records = $model::query()
            ->whereIn('id', array_values(array_unique(array_map('intval', $ids))))
            ->get();

        $terhapus = 0;
        $dilewati = [];

        DB::transaction(function () use ($records, $user, $jenis, &$terhapus, &$dilewati) {
            // Transaksi yang sudah dihapus tidak lagi menghalangi penghapusan
            // master; baris lunaknya dibersihkan permanen lebih dulu.
            $this->bersihkanYangTerkhapus($jenis, $records->pluck('id')->all());

            $keterangan = $this->keteranganPemakaian($jenis, $records->pluck('id')->all());

            foreach ($records as $record) {
                $alasan = $keterangan[$record->id] ?? '';

                if ($alasan !== '') {
                    $dilewati[] = $record->getLabel().' masih '.$alasan.' sehingga tidak bisa dihapus.';

                    continue;
                }

                activity('master')
                    ->performedOn($record)
                    ->causedBy($user)
                    ->withProperties(['data' => $record->toArray(), 'ip' => request()->ip()])
                    ->log('Menghapus '.mb_strtolower($this->label($jenis)));

                try {
                    $record->delete();
                    $terhapus++;
                } catch (QueryException) {
                    // Foreign key menolak hapus karena ada data lain yang menunjuk baris ini.
                    $dilewati[] = $record->getLabel().' masih dipakai data lain sehingga tidak bisa dihapus.';
                }
            }
        });

        return ['terhapus' => $terhapus, 'dilewati' => $dilewati];
    }

    /**
     * Hapus permanen transaksi yang sudah dihapus (soft delete) namun masih
     * menunjuk baris master yang akan dihapus, agar foreign key tidak menolak.
     *
     * @param  array<int, int>  $ids
     */
    private function bersihkanYangTerkhapus(string $jenis, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        foreach ($this->sumberPemakaian($jenis) as [$kelas, $kolom]) {
            if (in_array(SoftDeletes::class, class_uses_recursive($kelas), true)) {
                $kelas::onlyTrashed()->whereIn($kolom, $ids)->forceDelete();

                continue;
            }

            // Detail pengeluaran tidak memakai soft delete dan menggantung di
            // bawah induknya; hapus induknya supaya detail ikut terhapus.
            if ($kelas === DetailPengeluaran::class) {
                Pengeluaran::onlyTrashed()
                    ->whereHas('detail', fn ($query) => $query->whereIn($kolom, $ids))
                    ->forceDelete();
            }
        }
    }

    /**
     * Hitung baris aktif per foreign key. Baris yang sudah dihapus (soft delete)
     * tidak dihitung karena tidak terlihat di mana pun dan akan dibersihkan
     * saat master dihapus.
     *
     * @param  class-string<Model>  $kelas
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function hitung(string $kelas, string $kolom, array $ids): array
    {
        $query = $kelas::query();

        // Detail milik transaksi yang sudah dihapus ikut terhapus bersama
        // induknya, jadi tidak dihitung sebagai pemakaian.
        if ($kelas === DetailPengeluaran::class) {
            $query->whereHas('pengeluaran');
        }

        return $query
            ->whereIn($kolom, $ids)
            ->selectRaw($kolom.' as kunci, count(*) as jumlah')
            ->groupBy($kolom)
            ->pluck('jumlah', 'kunci')
            ->mapWithKeys(fn ($jumlah, $kunci) => [(int) $kunci => (int) $jumlah])
            ->all();
    }
}
