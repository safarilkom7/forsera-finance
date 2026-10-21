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
 * belum pernah dipakai; sisanya cukup dinonaktifkan (kolom "aktif").
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
     * Ringkasan pemakaian setiap baris, mis. "dipakai 2 pemasukan, 1 pengeluaran".
     * String kosong berarti baris aman dihapus.
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

        $sumber = match ($jenis) {
            'unit' => [
                'pemasukan' => $this->hitung(Pemasukan::class, 'unit_usaha_id', $ids),
                'pengeluaran' => $this->hitung(Pengeluaran::class, 'unit_usaha_id', $ids),
                'kategori' => $this->hitung(KategoriPemasukan::class, 'unit_usaha_id', $ids),
            ],
            'metode' => [
                'pemasukan' => $this->hitung(Pemasukan::class, 'metode_pembayaran_id', $ids),
                'pengeluaran' => $this->hitung(Pengeluaran::class, 'metode_pembayaran_id', $ids),
            ],
            'kategori' => [
                'pemasukan' => $this->hitung(Pemasukan::class, 'kategori_pemasukan_id', $ids),
            ],
            'jenis' => [
                'pengeluaran' => $this->hitung(Pengeluaran::class, 'jenis_pengeluaran_id', $ids),
                'item' => $this->hitung(Item::class, 'jenis_pengeluaran_id', $ids),
            ],
            'satuan' => [
                'item' => $this->hitung(Item::class, 'satuan_default_id', $ids),
                'detail pengeluaran' => $this->hitung(DetailPengeluaran::class, 'satuan_id', $ids),
            ],
            'item' => [
                'detail pengeluaran' => $this->hitung(DetailPengeluaran::class, 'item_id', $ids),
            ],
        };

        $keterangan = [];

        foreach ($sumber as $nama => $perId) {
            foreach ($perId as $id => $jumlah) {
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

        $keterangan = $this->keteranganPemakaian($jenis, $records->pluck('id')->all());
        $terhapus = 0;
        $dilewati = [];

        DB::transaction(function () use ($records, $keterangan, $user, $jenis, &$terhapus, &$dilewati) {
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
     * @param  class-string<Model>  $kelas
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function hitung(string $kelas, string $kolom, array $ids): array
    {
        $query = $kelas::query();

        // Transaksi yang sudah soft delete tetap menyimpan baris foreign key.
        if (in_array(SoftDeletes::class, class_uses_recursive($kelas), true)) {
            $query->withTrashed();
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
