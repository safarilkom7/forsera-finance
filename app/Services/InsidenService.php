<?php

namespace App\Services;

use App\Enums\ArahSelisih;
use App\Enums\JenisInsiden;
use App\Enums\StatusInsiden;
use App\Enums\StatusRekonsiliasi;
use App\Enums\StatusVerifikasi;
use App\Enums\TipePenyelesaian;
use App\Models\Insiden;
use App\Models\InsidenLog;
use App\Models\KategoriPemasukan;
use App\Models\Pemasukan;
use App\Models\Rekonsiliasi;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InsidenService
{
    public function __construct(
        private readonly PengaturanService $pengaturan,
        private readonly LampiranService $lampiran,
        private readonly PemasukanService $pemasukanService,
        private readonly NotifikasiService $notifikasi,
        private readonly PeriodeService $periode,
    ) {}

    public function buatOtomatis(Rekonsiliasi $rekonsiliasi, ?User $aktor = null): Insiden
    {
        $aktor ??= $rekonsiliasi->diverifikasiOleh ?? User::role(User::ROLE_OWNER)->first();

        if ($aktor === null) {
            throw new \RuntimeException('Tidak ada pengguna Owner untuk mencatat insiden.');
        }

        if ($rekonsiliasi->selisih === 0) {
            throw ValidationException::withMessages(['insiden' => 'Rekonsiliasi tidak memiliki selisih.']);
        }

        return DB::transaction(function () use ($rekonsiliasi, $aktor) {
            $arah = ArahSelisih::dari($rekonsiliasi->selisih);

            $insiden = Insiden::query()->create([
                'kode' => $this->kodeBaru(),
                'rekonsiliasi_id' => $rekonsiliasi->id,
                'jenis' => JenisInsiden::saran($arah),
                'arah' => $arah,
                'nominal_selisih' => abs($rekonsiliasi->selisih),
                'nominal_terselesaikan' => 0,
                'status' => StatusInsiden::OPEN,
                'dibuat_oleh' => $aktor->id,
            ]);

            $this->catat(
                $insiden,
                $aktor,
                'Insiden dibuat otomatis',
                'Rekonsiliasi '.$rekonsiliasi->kode.' menghasilkan selisih '.$arah->getLabel().' '.format_rupiah(abs($rekonsiliasi->selisih)).'.',
            );
            $this->notifikasi->insidenBaru($insiden->refresh(), $aktor);

            return $insiden;
        });
    }

    public function catat(Insiden $insiden, ?User $user, string $aksi, ?string $catatan = null, ?int $nominal = null, array $lampiran = []): InsidenLog
    {
        return InsidenLog::query()->create([
            'insiden_id' => $insiden->id,
            'user_id' => $user?->id,
            'aksi' => $aksi,
            'catatan' => $catatan,
            'nominal' => $nominal,
            'lampiran' => $lampiran === [] ? null : $lampiran,
            'created_at' => now(),
        ]);
    }

    public function mulaiInvestigasi(Insiden $insiden, User $user, ?string $catatan = null): Insiden
    {
        if ($insiden->status !== StatusInsiden::OPEN) {
            throw ValidationException::withMessages(['insiden' => 'Hanya insiden berstatus terbuka yang dapat diinvestigasi.']);
        }

        $insiden->update(['status' => StatusInsiden::INVESTIGASI]);
        $this->catat($insiden, $user, 'Mulai investigasi', $catatan);

        return $insiden->refresh();
    }

    public function tugaskan(Insiden $insiden, ?User $penanggungJawab, User $user): Insiden
    {
        $insiden->update(['penanggung_jawab_id' => $penanggungJawab?->id]);

        $this->catat(
            $insiden,
            $user,
            'Mengubah penanggung jawab',
            $penanggungJawab === null ? 'Penanggung jawab dikosongkan.' : 'Penanggung jawab: '.$penanggungJawab->name,
        );

        return $insiden->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function selesaikan(Insiden $insiden, array $data, User $user): Insiden
    {
        if ($insiden->status->final()) {
            throw ValidationException::withMessages(['insiden' => 'Insiden sudah ditutup dan tidak dapat diubah.']);
        }

        $tipe = TipePenyelesaian::tryFrom((string) ($data['tipe_penyelesaian'] ?? ''));

        if ($tipe === null) {
            throw ValidationException::withMessages(['tipe_penyelesaian' => 'Tipe penyelesaian wajib dipilih.']);
        }

        $penyebab = trim((string) ($data['penyebab'] ?? ''));
        $catatan = trim((string) ($data['catatan_penyelesaian'] ?? ''));

        if ($penyebab === '') {
            throw ValidationException::withMessages(['penyebab' => 'Penyebab wajib diisi.']);
        }

        if ($catatan === '') {
            throw ValidationException::withMessages(['catatan_penyelesaian' => 'Catatan penyelesaian wajib diisi.']);
        }

        if ($tipe === TipePenyelesaian::WRITE_OFF && ! $user->isOwner()) {
            throw ValidationException::withMessages(['tipe_penyelesaian' => 'Write-off hanya dapat dilakukan Owner.']);
        }

        if ($tipe === TipePenyelesaian::CATAT_PENDAPATAN_LAIN && $insiden->arah !== ArahSelisih::LEBIH) {
            throw ValidationException::withMessages(['tipe_penyelesaian' => 'Catat pendapatan lain hanya untuk selisih LEBIH.']);
        }

        $bukti = [];

        if (isset($data['lampiran']) && is_array($data['lampiran'])) {
            $bukti = array_values(array_filter(
                $data['lampiran'],
                fn ($item) => is_array($item) && isset($item['path']),
            ));
        } elseif (! empty($data['lampiran_files'])) {
            $bukti = $this->lampiran->dariPaths(
                (array) $data['lampiran_files'],
                (array) ($data['lampiran_nama_asli'] ?? []),
                $user,
            );
        }

        if ($tipe === TipePenyelesaian::KOREKSI_DATA) {
            $toleransi = $this->pengaturan->getInt('toleransi_selisih', 1000);

            if (abs($insiden->rekonsiliasi->selisih) > $toleransi) {
                throw ValidationException::withMessages([
                    'tipe_penyelesaian' => 'Selisih rekonsiliasi masih di atas toleransi. Koreksi data pemasukan terlebih dahulu.',
                ]);
            }
        }

        return DB::transaction(function () use ($insiden, $data, $user, $tipe, $penyebab, $catatan, $bukti) {
            $insiden->update(['penyebab' => $penyebab]);

            if ($tipe === TipePenyelesaian::CATAT_PENDAPATAN_LAIN) {
                $this->catatPendapatanLain($insiden, $user);
                $this->tutup($insiden, $tipe, $catatan, $penyebab, StatusInsiden::SELESAI, $user, $bukti);

                return $insiden->refresh();
            }

            if ($tipe === TipePenyelesaian::WRITE_OFF) {
                $this->tutup($insiden, $tipe, $catatan, $penyebab, StatusInsiden::DIHAPUSKAN, $user, $bukti);

                return $insiden->refresh();
            }

            if ($tipe === TipePenyelesaian::KOREKSI_DATA) {
                $this->tutup($insiden, $tipe, $catatan, $penyebab, StatusInsiden::SELESAI, $user, $bukti);

                return $insiden->refresh();
            }

            // SETORAN_SUSULAN, DANA_CAIR_SUSULAN, GANTI_RUGI
            $nominal = parse_rupiah($data['nominal'] ?? 0);

            if ($nominal <= 0) {
                throw ValidationException::withMessages(['nominal' => 'Nominal penerimaan wajib diisi dan lebih dari nol.']);
            }

            $total = min($insiden->nominal_selisih, $insiden->nominal_terselesaikan + $nominal);
            $insiden->update(['nominal_terselesaikan' => $total]);

            $this->catat(
                $insiden,
                $user,
                'Mencatat '.$tipe->getLabel(),
                $catatan,
                $nominal,
                $bukti,
            );

            if ($total >= $insiden->nominal_selisih) {
                $this->tutup($insiden, $tipe, $catatan, $penyebab, StatusInsiden::SELESAI, $user, $bukti);
            } elseif (! empty($bukti)) {
                $insiden->update(['lampiran' => array_merge($insiden->lampiran ?? [], $bukti)]);
            }

            return $insiden->refresh();
        });
    }

    public function selesaikanKarenaKoreksi(Insiden $insiden, User $user, string $alasan = 'Selisih sudah dalam toleransi setelah koreksi data.'): Insiden
    {
        if ($insiden->status->final()) {
            return $insiden;
        }

        return DB::transaction(function () use ($insiden, $user, $alasan) {
            $this->tutup(
                $insiden,
                TipePenyelesaian::KOREKSI_DATA,
                $alasan,
                $insiden->penyebab ?? 'Koreksi data oleh Owner',
                StatusInsiden::SELESAI,
                $user,
                [],
            );

            return $insiden->refresh();
        });
    }

    public function batalkanKarenaRekonsiliasi(Insiden $insiden, User $user): Insiden
    {
        $insiden->update([
            'status' => StatusInsiden::DIBATALKAN,
            'ditutup_oleh' => $user->id,
            'ditutup_at' => now(),
        ]);

        $this->catat($insiden, $user, 'Insiden dibatalkan', 'Rekonsiliasi terkait dibatalkan oleh Owner.');

        return $insiden->refresh();
    }

    public function tutupRekonsiliasiBilaSelesai(Rekonsiliasi $rekonsiliasi): void
    {
        if ($rekonsiliasi->status === StatusRekonsiliasi::DIBATALKAN) {
            return;
        }

        $masihTerbuka = $rekonsiliasi->insiden()->terbuka()->exists();

        if ($masihTerbuka) {
            return;
        }

        $rekonsiliasi->update(['status' => StatusRekonsiliasi::SELESAI]);
        $rekonsiliasi->pemasukan()->update(['status_verifikasi' => StatusVerifikasi::SELESAI->value]);

        app(DashboardService::class)->flush();
    }

    public function kodeBaru(): string
    {
        $prefix = 'INC-'.now()->format('Ym').'-';
        $terakhir = Insiden::query()
            ->where('kode', 'like', $prefix.'%')
            ->orderByDesc('kode')
            ->value('kode');

        $nomor = $terakhir === null ? 1 : ((int) substr($terakhir, -4)) + 1;

        return $prefix.str_pad((string) $nomor, 4, '0', STR_PAD_LEFT);
    }

    private function catatPendapatanLain(Insiden $insiden, User $user): Pemasukan
    {
        if ($insiden->pemasukanLain()->exists()) {
            throw ValidationException::withMessages(['insiden' => 'Pemasukan lain untuk insiden ini sudah tercatat.']);
        }

        $rekonsiliasi = $insiden->rekonsiliasi;
        $kategori = KategoriPemasukan::query()->firstOrCreate(
            ['nama' => 'Pendapatan Lain-lain', 'unit_usaha_id' => null],
            ['aktif' => true],
        );

        $tanggal = $rekonsiliasi->tanggal_diterima ?? Carbon::today();
        $this->periode->pastikanTerbuka($tanggal, $rekonsiliasi->unit_usaha_id);

        $pemasukan = $this->pemasukanService->buat([
            'tanggal' => $tanggal->toDateString(),
            'unit_usaha_id' => $rekonsiliasi->unit_usaha_id,
            'kategori_pemasukan_id' => $kategori->id,
            'metode_pembayaran_id' => $rekonsiliasi->metode_pembayaran_id,
            'nominal' => $insiden->nominal_selisih,
            'note' => 'Penyelesaian insiden '.$insiden->kode.' (selisih lebih)',
        ], $user);

        $pemasukan->update([
            'status_verifikasi' => StatusVerifikasi::SELESAI,
            'insiden_id' => $insiden->id,
        ]);

        $this->catat(
            $insiden,
            $user,
            'Mencatat pendapatan lain',
            'Pemasukan '.format_rupiah($insiden->nominal_selisih).' dicatat sebagai Pendapatan Lain-lain.',
            $insiden->nominal_selisih,
        );

        return $pemasukan;
    }

    /**
     * @param  array<int, array<string, mixed>>  $bukti
     */
    private function tutup(
        Insiden $insiden,
        TipePenyelesaian $tipe,
        string $catatan,
        string $penyebab,
        StatusInsiden $status,
        User $user,
        array $bukti,
    ): void {
        $lampiran = $insiden->lampiran ?? [];

        if ($bukti !== []) {
            $lampiran = array_merge($lampiran, $bukti);
        }

        $insiden->update([
            'tipe_penyelesaian' => $tipe,
            'penyebab' => $penyebab,
            'catatan_penyelesaian' => $catatan,
            'status' => $status,
            'lampiran' => $lampiran,
            'ditutup_oleh' => $user->id,
            'ditutup_at' => now(),
        ]);

        $this->catat(
            $insiden,
            $user,
            'Insiden '.$status->getLabel(),
            $catatan,
            null,
            $bukti,
        );

        $this->notifikasi->penyelesaianInsiden($insiden->refresh(), $user);

        if ($insiden->rekonsiliasi !== null) {
            $this->tutupRekonsiliasiBilaSelesai($insiden->rekonsiliasi->refresh());
        }
    }
}
