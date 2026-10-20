<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PeriodeFilterService
{
    /**
     * @return array{preset: string, mulai: Carbon, selesai: Carbon, label: string, unit_id: int|null}
     */
    public function resolve(Request $request, User $user): array
    {
        $preset = (string) $request->query('periode', 'bulan_ini');

        $mulai = now()->startOfDay();
        $selesai = now()->endOfDay();

        switch ($preset) {
            case 'hari_ini':
                $mulai = now()->startOfDay();
                $selesai = now()->endOfDay();
                break;

            case '7_hari':
                $mulai = now()->subDays(6)->startOfDay();
                $selesai = now()->endOfDay();
                break;

            case 'bulan_lalu':
                $mulai = now()->subMonthNoOverflow()->startOfMonth();
                $selesai = now()->subMonthNoOverflow()->endOfMonth();
                break;

            case 'tahun_ini':
                $mulai = now()->startOfYear();
                $selesai = now()->endOfYear();
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
                break;

            case 'bulan_ini':
            default:
                $preset = 'bulan_ini';
                $mulai = now()->startOfMonth();
                $selesai = now()->endOfMonth();
                break;
        }

        if ($mulai->diffInDays($selesai) > 366) {
            abort(422, 'Rentang periode maksimal satu tahun.');
        }

        $unitId = $request->integer('unit') ?: null;

        if ($unitId !== null && ! $user->bolehAksesUnit($unitId)) {
            abort(403, 'Anda tidak memiliki akses ke unit usaha ini.');
        }

        $label = match ($preset) {
            'hari_ini' => 'Hari ini',
            '7_hari' => '7 hari terakhir',
            'bulan_lalu' => 'Bulan lalu',
            'tahun_ini' => 'Tahun ini',
            'custom' => format_tanggal($mulai).' – '.format_tanggal($selesai),
            default => 'Bulan ini',
        };

        return [
            'preset' => $preset,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'label' => $label,
            'unit_id' => $unitId,
        ];
    }
}
