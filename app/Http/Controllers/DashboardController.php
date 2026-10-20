<?php

namespace App\Http\Controllers;

use App\Models\UnitUsaha;
use App\Services\DashboardService;
use App\Services\PeriodeFilterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, PeriodeFilterService $filter, DashboardService $dashboard): View
    {
        $user = $request->user();
        $periode = $filter->resolve($request, $user);

        $units = UnitUsaha::query()
            ->when($user->isStaf(), fn ($q) => $q->whereIn('id', $user->unitUsaha()->select('unit_usaha.id')))
            ->orderBy('nama')
            ->get();

        $data = [
            'user' => $user,
            'periode' => $periode,
            'units' => $units,
            'pilihan_periode' => $filter->pilihan(),
        ];

        if ($user->isStaf()) {
            $data['ringkasan_staf'] = $dashboard->ringkasanStaf($user, $periode['unit_id']);

            return view('dashboard-staf', $data);
        }

        $mulai = $periode['mulai'];
        $selesai = $periode['selesai'];
        $unitId = $periode['unit_id'];

        $data += [
            'ringkasan' => $dashboard->ringkasan($mulai, $selesai, $unitId),
            'tren' => $dashboard->trenHarian($mulai, $selesai, $unitId),
            'pemasukan_unit_chart' => $dashboard->pemasukanPerUnit($mulai, $selesai, $unitId),
            'komposisi_metode' => $dashboard->komposisiMetode($mulai, $selesai, $unitId),
            'top_jenis' => $dashboard->topJenisPengeluaran($mulai, $selesai, $unitId),
            'tabel_unit' => $dashboard->ringkasanPerUnit($mulai, $selesai, $unitId),
            'perhatian' => $dashboard->perluPerhatian($user, $unitId),
        ];

        return view('dashboard', $data);
    }
}
