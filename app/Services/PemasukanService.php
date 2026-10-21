<?php

namespace App\Services;

use App\Enums\StatusVerifikasi;
use App\Models\KategoriPemasukan;
use App\Models\MetodePembayaran;
use App\Models\Pemasukan;
use App\Models\Rekonsiliasi;
use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PemasukanService
{
    public function __construct(
        private readonly PengaturanService $pengaturan,
        private readonly PeriodeService $periode,
        private readonly LampiranService $lampiran,
    ) {}

    public function buat(array $data, User $user): Pemasukan
    {
        $data = $this->normalisasi($data);
        $this->validasi($data, $user);
        $this->periode->pastikanTerbuka($data['tanggal'], (int) $data['unit_usaha_id']);

        return DB::transaction(function () use ($data, $user) {
            $lampiran = $this->lampiranDariData($data, $user);

            $pemasukan = Pemasukan::query()->create([
                'tanggal' => $data['tanggal'],
                'unit_usaha_id' => $data['unit_usaha_id'],
                'kategori_pemasukan_id' => $data['kategori_pemasukan_id'],
                'metode_pembayaran_id' => $data['metode_pembayaran_id'],
                'nominal' => parse_rupiah($data['nominal']),
                'note' => $data['note'] ?? null,
                'lampiran' => $lampiran,
                'status_verifikasi' => StatusVerifikasi::BELUM_DICEK,
                'dibuat_oleh' => $user->id,
            ]);

            $user->update(['last_unit_usaha_id' => $pemasukan->unit_usaha_id]);

            return $pemasukan;
        });
    }

    public function ubah(Pemasukan $pemasukan, array $data, User $user): Pemasukan
    {
        if ($pemasukan->terkunci()) {
            throw ValidationException::withMessages([
                'pemasukan' => 'Pemasukan sudah diverifikasi dan terkunci. Hanya Owner yang dapat melakukan koreksi data terkunci.',
            ]);
        }

        if (! $pemasukan->bisaDiubahOleh($user)) {
            throw ValidationException::withMessages(['pemasukan' => 'Anda tidak berhak mengubah pemasukan ini.']);
        }

        $data = $this->normalisasi($data);
        $this->validasi($data, $user);
        $this->periode->pastikanTerbuka($data['tanggal'], (int) $data['unit_usaha_id']);
        $this->periode->pastikanTerbuka($pemasukan->tanggal, (int) $pemasukan->unit_usaha_id);

        return DB::transaction(function () use ($pemasukan, $data, $user) {
            $lampiran = $pemasukan->lampiran ?? [];

            if (! empty($data['lampiran_diubah'])) {
                $baru = $this->lampiranDariData($data, $user);
                $this->lampiran->hapusYangTidakDipakai($lampiran, $baru);
                $lampiran = $baru;
            }

            $pemasukan->update([
                'tanggal' => $data['tanggal'],
                'unit_usaha_id' => $data['unit_usaha_id'],
                'kategori_pemasukan_id' => $data['kategori_pemasukan_id'],
                'metode_pembayaran_id' => $data['metode_pembayaran_id'],
                'nominal' => parse_rupiah($data['nominal']),
                'note' => $data['note'] ?? null,
                'lampiran' => $lampiran,
                'diubah_oleh' => $user->id,
            ]);

            return $pemasukan->refresh();
        });
    }

    /**
     * Koreksi data terkunci oleh Owner (aturan 20). Nilai lama & baru tercatat di audit log.
     */
    public function koreksi(Pemasukan $pemasukan, array $data, string $alasan, User $user): Pemasukan
    {
        if (! $user->isOwner()) {
            throw ValidationException::withMessages(['pemasukan' => 'Hanya Owner yang dapat mengoreksi data terkunci.']);
        }

        if (trim($alasan) === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan koreksi wajib diisi.']);
        }

        if ($pemasukan->rekonsiliasi_id !== null) {
            $rekonsiliasi = $pemasukan->rekonsiliasi;
            $data['unit_usaha_id'] = $data['unit_usaha_id'] ?? $pemasukan->unit_usaha_id;
            $data['metode_pembayaran_id'] = $data['metode_pembayaran_id'] ?? $pemasukan->metode_pembayaran_id;

            if ((int) $data['unit_usaha_id'] !== $rekonsiliasi->unit_usaha_id
                || (int) $data['metode_pembayaran_id'] !== $rekonsiliasi->metode_pembayaran_id) {
                throw ValidationException::withMessages([
                    'pemasukan' => 'Unit dan metode pemasukan yang sudah terhubung rekonsiliasi tidak boleh diubah.',
                ]);
            }

            $tanggal = Carbon::parse($data['tanggal'] ?? $pemasukan->tanggal);
            if ($tanggal->lt($rekonsiliasi->periode_mulai) || $tanggal->gt($rekonsiliasi->periode_selesai)) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Tanggal koreksi harus tetap berada dalam rentang periode rekonsiliasi terkait.',
                ]);
            }
        }

        $data = $this->normalisasi(array_merge($pemasukan->only([
            'tanggal', 'unit_usaha_id', 'kategori_pemasukan_id', 'metode_pembayaran_id', 'nominal', 'note',
        ]), $data));

        $this->validasi($data, $user);
        $this->periode->pastikanTerbuka($data['tanggal'], (int) $data['unit_usaha_id']);

        $lama = $pemasukan->only(['tanggal', 'nominal', 'note', 'kategori_pemasukan_id']);

        return DB::transaction(function () use ($pemasukan, $data, $alasan, $user, $lama) {
            $pemasukan->update([
                'tanggal' => $data['tanggal'],
                'nominal' => parse_rupiah($data['nominal']),
                'note' => $data['note'] ?? null,
                'kategori_pemasukan_id' => $data['kategori_pemasukan_id'],
                'diubah_oleh' => $user->id,
            ]);

            activity('pemasukan')
                ->performedOn($pemasukan)
                ->causedBy($user)
                ->withProperties(['alasan' => $alasan, 'lama' => $lama, 'baru' => $data])
                ->log('Koreksi data terkunci');

            if ($pemasukan->rekonsiliasi_id !== null) {
                app(RekonsiliasiService::class)->hitungUlang(
                    $pemasukan->rekonsiliasi,
                    'Koreksi pemasukan: '.$alasan,
                    $user,
                );
            }

            return $pemasukan->refresh();
        });
    }

    public function hapus(Pemasukan $pemasukan, User $user): void
    {
        if ($pemasukan->terkunci() || $pemasukan->rekonsiliasi_id !== null) {
            throw ValidationException::withMessages([
                'pemasukan' => 'Pemasukan yang sudah diverifikasi tidak dapat dihapus.',
            ]);
        }

        if (! $pemasukan->bisaDiubahOleh($user)) {
            throw ValidationException::withMessages(['pemasukan' => 'Anda tidak berhak menghapus pemasukan ini.']);
        }

        $this->periode->pastikanTerbuka($pemasukan->tanggal, $pemasukan->unit_usaha_id);

        DB::transaction(function () use ($pemasukan, $user) {
            activity('pemasukan')
                ->performedOn($pemasukan)
                ->causedBy($user)
                ->withProperties(['lama' => $pemasukan->toArray()])
                ->log('Menghapus pemasukan');

            $pemasukan->delete();
        });
    }

    /**
     * Hapus beberapa pemasukan sekaligus. Baris yang terkunci atau sudah
     * direkonsiliasi dilewati dan alasannya dilaporkan ke pemanggil.
     *
     * @param  array<int, int>  $ids
     * @return array{terhapus: int, dilewati: array<int, string>}
     */
    public function hapusBanyak(array $ids, User $user): array
    {
        $records = Pemasukan::query()
            ->whereIn('id', array_values(array_unique(array_map('intval', $ids))))
            ->get();

        $terhapus = 0;
        $dilewati = [];

        foreach ($records as $pemasukan) {
            try {
                $this->hapus($pemasukan, $user);
                $terhapus++;
            } catch (ValidationException $exception) {
                $dilewati[] = $exception->validator->errors()->first();
            }
        }

        return ['terhapus' => $terhapus, 'dilewati' => $dilewati];
    }

    private function normalisasi(array $data): array
    {
        $data['tanggal'] = isset($data['tanggal']) ? Carbon::parse($data['tanggal'])->toDateString() : now()->toDateString();
        $data['nominal'] = parse_rupiah($data['nominal'] ?? 0);

        return $data;
    }

    private function validasi(array $data, User $user): void
    {
        $validator = Validator::make($data, [
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'unit_usaha_id' => ['required', 'exists:unit_usaha,id'],
            'kategori_pemasukan_id' => ['required', 'exists:kategori_pemasukan,id'],
            'metode_pembayaran_id' => ['required', 'exists:metode_pembayaran,id'],
            'nominal' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal.before_or_equal' => 'Tanggal pemasukan tidak boleh di masa depan.',
            'nominal.required' => 'Nominal pemasukan wajib diisi.',
            'nominal.min' => 'Nominal pemasukan harus lebih dari nol.',
            'kategori_pemasukan_id.required' => 'Kategori pemasukan wajib dipilih.',
            'metode_pembayaran_id.required' => 'Metode pembayaran wajib dipilih.',
            'unit_usaha_id.required' => 'Unit usaha wajib dipilih.',
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

            $kategori = KategoriPemasukan::query()->find($data['kategori_pemasukan_id'] ?? null);

            if ($kategori === null || ! $kategori->aktif) {
                $validator->errors()->add('kategori_pemasukan_id', 'Kategori pemasukan tidak aktif.');
            } elseif ($kategori->unit_usaha_id !== null && (int) $kategori->unit_usaha_id !== (int) ($data['unit_usaha_id'] ?? 0)) {
                $validator->errors()->add('kategori_pemasukan_id', 'Kategori tersebut bukan milik unit usaha yang dipilih.');
            }

            $maks = $this->pengaturan->getInt('nominal_maks_transaksi', 2_000_000_000);
            if (parse_rupiah($data['nominal'] ?? 0) > $maks) {
                $validator->errors()->add('nominal', 'Nominal melebihi batas maksimum transaksi '.format_rupiah($maks).'.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
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

        return $this->lampiran->dariPaths(
            $paths,
            $data['lampiran_nama_asli'] ?? [],
            $user,
        );
    }
}
