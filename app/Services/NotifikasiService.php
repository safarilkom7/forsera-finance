<?php

namespace App\Services;

use App\Enums\StatusRekonsiliasi;
use App\Enums\StatusVerifikasi;
use App\Models\Insiden;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Rekonsiliasi;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class NotifikasiService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function penggunaInternal()
    {
        return User::query()
            ->where('aktif', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [User::ROLE_OWNER, User::ROLE_ADMIN]))
            ->get();
    }

    /**
     * @param  iterable<int, User>  $penerima
     */
    public function kirim(iterable $penerima, string $judul, string $pesan, ?string $url = null, string $ikon = 'heroicon-o-bell', string $warna = 'warning'): void
    {
        $daftar = collect($penerima)->filter()->unique('id');

        if ($daftar->isEmpty()) {
            return;
        }

        Notification::send($daftar, new SystemNotification($judul, $pesan, $url, $ikon, $warna));
    }

    public function insidenBaru(Insiden $insiden, ?User $aktor = null): void
    {
        $this->kirim(
            $this->penggunaInternal(),
            'Insiden baru '.$insiden->kode,
            'Selisih '.format_rupiah($insiden->nominal_selisih).' pada '.$insiden->rekonsiliasi->kode.' perlu ditindaklanjuti.',
            route('insiden.show', $insiden),
            'heroicon-o-exclamation-triangle',
            'danger',
        );
    }

    public function penyelesaianInsiden(Insiden $insiden, User $aktor): void
    {
        $this->kirim(
            $this->penggunaInternal(),
            'Insiden '.$insiden->kode.' '.strtolower($insiden->status->getLabel()),
            'Penyelesaian dicatat oleh '.$aktor->name.'.',
            route('insiden.show', $insiden),
            'heroicon-o-check-circle',
            'success',
        );
    }

    public function pengeluaranMenunggu(Pengeluaran $pengeluaran): void
    {
        $this->kirim(
            User::role(User::ROLE_OWNER)->where('aktif', true)->get(),
            'Pengeluaran menunggu persetujuan',
            $pengeluaran->jenis->nama.' '.format_rupiah($pengeluaran->total_nominal).' dari '.$pengeluaran->dibuatOleh->name.' menunggu persetujuan Anda.',
            route('pengeluaran.index'),
            'heroicon-o-clock',
            'warning',
        );
    }

    public function rekonsiliasiBaru(Rekonsiliasi $rekonsiliasi, User $aktor): void
    {
        if ($rekonsiliasi->status === StatusRekonsiliasi::SELISIH) {
            $this->kirim(
                $this->penggunaInternal(),
                'Rekonsiliasi '.$rekonsiliasi->kode.' berselisih',
                'Selisih '.format_rupiah($rekonsiliasi->selisih).' pada '.$rekonsiliasi->unitUsaha->nama.' ('.$rekonsiliasi->metode->nama.').',
                route('rekonsiliasi.show', $rekonsiliasi),
                'heroicon-o-scale',
                'danger',
            );
        }
    }

    public function pengingatHarian(): void
    {
        $hariIni = now()->toDateString();

        if (Cache::has('pengingat.'.$hariIni)) {
            return;
        }

        $pengaturan = app(PengaturanService::class);
        $batasVerifikasi = $pengaturan->getInt('batas_hari_verifikasi', 2);
        $batasSettlement = $pengaturan->getInt('batas_hari_settlement', 3);

        $belumDicek = Pemasukan::query()
            ->belumDicek()
            ->whereDate('tanggal', '<=', now()->subDays($batasVerifikasi))
            ->with(['unitUsaha', 'metode'])
            ->get()
            ->groupBy(fn (Pemasukan $p) => $p->unit_usaha_id.'-'.$p->metode_pembayaran_id);

        foreach ($belumDicek as $grup) {
            $pertama = $grup->first();
            $this->kirim(
                $this->penggunaInternal(),
                'Pemasukan belum diverifikasi',
                $grup->count().' pemasukan '.$pertama->unitUsaha->nama.' ('.$pertama->metode->nama.') senilai '.format_rupiah($grup->sum('nominal')).' melewati batas verifikasi.',
                route('pemasukan.index'),
                'heroicon-o-clock',
                'warning',
            );
        }

        $menungguSettlement = Pemasukan::query()
            ->where('status_verifikasi', StatusVerifikasi::MENUNGGU_SETTLEMENT->value)
            ->whereDate('tanggal', '<=', now()->subDays($batasSettlement))
            ->with(['unitUsaha', 'metode'])
            ->get()
            ->groupBy(fn (Pemasukan $p) => $p->unit_usaha_id.'-'.$p->metode_pembayaran_id);

        foreach ($menungguSettlement as $grup) {
            $pertama = $grup->first();
            $this->kirim(
                $this->penggunaInternal(),
                'Settlement belum diterima',
                format_rupiah($grup->sum('nominal')).' dari '.$pertama->unitUsaha->nama.' ('.$pertama->metode->nama.') sudah menunggu settlement lebih lama dari '.$batasSettlement.' hari.',
                route('pemasukan.index'),
                'heroicon-o-banknotes',
                'warning',
            );
        }

        $menungguPersetujuan = Pengeluaran::query()
            ->where('status_persetujuan', 'MENUNGGU')
            ->whereDate('tanggal', '<=', now()->subDay())
            ->get();

        if ($menungguPersetujuan->isNotEmpty()) {
            $this->kirim(
                User::role(User::ROLE_OWNER)->where('aktif', true)->get(),
                'Pengeluaran menunggu persetujuan',
                $menungguPersetujuan->count().' pengeluaran senilai '.format_rupiah($menungguPersetujuan->sum('total_nominal')).' belum disetujui.',
                route('pengeluaran.index'),
                'heroicon-o-check-badge',
                'warning',
            );
        }

        Cache::put('pengingat.'.$hariIni, true, now()->addDay());
    }
}
