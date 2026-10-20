<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PeriodeFilterService
{
    public function __construct(private readonly PeriodeAkuntansiService $akuntansi) {}

    /**
     * @return array{preset: string, mulai: Carbon, selesai: Carbon, label: string, unit_id: int|null, tanggal_mulai: int}
     */
    public function resolve(Request $request, User $user): array
    {
        $preset = (string) $request->query('periode', 'periode_ini');

        // Alias lama agar tautan/bookmark "bulan_ini"/"bulan_lalu" tetap berjalan.
        $preset = match ($preset) {
            'bulan_ini' => 'periode_ini',
            'bulan_lalu' => 'periode_lalu',
            default => $preset,
        };

        $berjalan = $this->akuntansi->berjalan();

        $mulai = now()->startOfDay();
        $selesai = now()->endOfDay();
        $label = 'Hari ini';

        switch ($preset) {
            case 'hari_ini':
                $label = 'Hari ini';
                break;

            case '7_hari':
                $mulai = now()->subDays(6)->startOfDay();
                $selesai = now()->endOfDay();
                $label = '7 hari terakhir';
                break;

            case 'periode_lalu':
                $lalu = $this->akuntansi->sebelumnya();
                $mulai = $lalu['mulai'];
                $selesai = $lalu['selesai'];
                $label = 'Periode lalu '.$lalu['rentang'];
                break;

            case 'tahun_ini':
                $mulai = now()->startOfYear();
                $selesai = now()->endOfYear();
                $label = 'Tahun ini';
                break;

            case 'custom':
                $data = $request->validate([
                    'dari' => ['required', 'date'],
                    'sampai' => ['required', 'date', 'after_or_equal:dari'],
                ], [
                    'dari.required' => 'Tanggal mulai wajib diisi.',
                    'sampai.required' => 'Tanggal akhir wajib diisi.',
                    'sampai.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
                ]);

                $mulai = Carbon::parse($data['dari'])->startOfDay();
                $selesai = Carbon::parse($data['sampai'])->endOfDay();
                $label = format_tanggal($mulai).' – '.format_tanggal($selesai);
                break;

            case 'periode_ini':
            default:
                $preset = 'periode_ini';
                $mulai = $berjalan['mulai'];
                $selesai = $berjalan['selesai'];
                $label = 'Periode '.$berjalan['rentang'];
                break;
        }

        if ($mulai->diffInDays($selesai) > 366) {
            abort(422, 'Rentang periode maksimal satu tahun.');
        }

        $unitId = $request->integer('unit') ?: null;

        if ($unitId !== null && ! $user->bolehAksesUnit($unitId)) {
            abort(403, 'Anda tidak memiliki akses ke unit usaha ini.');
        }

        return [
            'preset' => $preset,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'label' => $label,
            'unit_id' => $unitId,
            'tanggal_mulai' => $this->akuntansi->tanggalMulai(),
        ];
    }

    /**
     * Pilihan preset untuk dropdown dashboard/laporan.
     *
     * @return array<string, string>
     */
    public function pilihan(): array
    {
        return [
            'hari_ini' => 'Hari ini',
            '7_hari' => '7 hari terakhir',
            'periode_ini' => 'Periode berjalan',
            'periode_lalu' => 'Periode sebelumnya',
            'tahun_ini' => 'Tahun ini',
            'custom' => 'Custom',
        ];
    }
}
