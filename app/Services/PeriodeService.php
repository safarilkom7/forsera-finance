<?php

namespace App\Services;

use App\Models\PeriodeTutup;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Facades\Activity;

class PeriodeService
{
    public function terkunci(CarbonInterface|string $tanggal, ?int $unitUsahaId): bool
    {
        $carbon = $tanggal instanceof CarbonInterface ? $tanggal : Carbon::parse($tanggal);

        return PeriodeTutup::query()
            ->aktif()
            ->where('tahun', $carbon->year)
            ->where('bulan', $carbon->month)
            ->where(function ($q) use ($unitUsahaId) {
                $q->whereNull('unit_usaha_id');

                if ($unitUsahaId !== null) {
                    $q->orWhere('unit_usaha_id', $unitUsahaId);
                }
            })
            ->exists();
    }

    /**
     * @throws ValidationException
     */
    public function pastikanTerbuka(CarbonInterface|string $tanggal, ?int $unitUsahaId): void
    {
        if ($this->terkunci($tanggal, $unitUsahaId)) {
            $carbon = $tanggal instanceof CarbonInterface ? $tanggal : Carbon::parse($tanggal);

            throw ValidationException::withMessages([
                'tanggal' => 'Periode '.$carbon->translatedFormat('F Y').' sudah ditutup. Data tidak dapat diubah.',
            ]);
        }
    }

    public function tutup(int $tahun, int $bulan, ?int $unitUsahaId, User $user): PeriodeTutup
    {
        if ($bulan < 1 || $bulan > 12) {
            throw ValidationException::withMessages(['bulan' => 'Bulan tidak valid.']);
        }

        if (PeriodeTutup::query()->aktif()->where('tahun', $tahun)->where('bulan', $bulan)
            ->where('unit_usaha_id', $unitUsahaId)->exists()) {
            throw ValidationException::withMessages(['bulan' => 'Periode ini sudah ditutup.']);
        }

        $periode = PeriodeTutup::query()->create([
            'tahun' => $tahun,
            'bulan' => $bulan,
            'unit_usaha_id' => $unitUsahaId,
            'ditutup_oleh' => $user->id,
            'ditutup_at' => now(),
        ]);

        Activity::useLogName('periode')
            ->performedOn($periode)
            ->causedBy($user)
            ->withProperties(['tahun' => $tahun, 'bulan' => $bulan, 'unit_usaha_id' => $unitUsahaId])
            ->log('Menutup periode');

        return $periode;
    }

    public function bukaKembali(PeriodeTutup $periode, string $alasan, User $user): PeriodeTutup
    {
        if (! $user->isOwner()) {
            throw ValidationException::withMessages(['alasan' => 'Hanya Owner yang dapat membuka kembali periode.']);
        }

        if ($periode->dibuka_kembali_at !== null) {
            throw ValidationException::withMessages(['alasan' => 'Periode ini sudah dibuka kembali.']);
        }

        if (trim($alasan) === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan wajib diisi.']);
        }

        $periode->update([
            'dibuka_kembali_oleh' => $user->id,
            'dibuka_kembali_at' => now(),
            'alasan' => $alasan,
        ]);

        Activity::useLogName('periode')
            ->performedOn($periode)
            ->causedBy($user)
            ->withProperties(['alasan' => $alasan])
            ->log('Membuka kembali periode');

        return $periode;
    }
}
