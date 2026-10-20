<?php

namespace App\Http\Controllers;

use App\Exports\LaporanGabunganExport;
use App\Models\UnitUsaha;
use App\Services\LaporanService;
use App\Services\PeriodeFilterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function __construct(
        private readonly LaporanService $service,
        private readonly PeriodeFilterService $filter,
    ) {}

    public function index(Request $request): View
    {
        $data = $this->data($request);

        return view('laporan.index', $data);
    }

    public function exportExcel(Request $request)
    {
        $data = $this->data($request);

        return Excel::download(
            new LaporanGabunganExport($data),
            'laporan-keuangan-'.$data['periode']['mulai']->toDateString().'-'.$data['periode']['selesai']->toDateString().'.xlsx',
        );
    }

    public function exportPdf(Request $request)
    {
        $data = $this->data($request);

        $pdf = Pdf::loadView('laporan.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOption(['isRemoteEnabled' => false]);

        return $pdf->download('laporan-keuangan-'.$data['periode']['mulai']->toDateString().'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function data(Request $request): array
    {
        $user = $request->user();
        $periode = $this->filter->resolve($request, $user);

        $units = UnitUsaha::query()
            ->where('aktif', true)
            ->when($periode['unit_id'], fn ($q, $unit) => $q->whereKey($unit))
            ->orderBy('nama')
            ->get();

        $mulai = $periode['mulai'];
        $selesai = $periode['selesai'];
        $unitId = $periode['unit_id'];

        $bukuKas = $units->map(fn (UnitUsaha $unit) => [
            'unit' => $unit,
            'baris' => $this->service->bukuKas($mulai, $selesai, $unit->id),
        ])->all();

        return [
            'periode' => $periode,
            'pilihan_periode' => $this->filter->pilihan(),
            'units' => UnitUsaha::query()->where('aktif', true)->orderBy('nama')->get(),
            'laba_rugi' => $this->service->labaRugi($mulai, $selesai, $unitId),
            'pemasukan_kategori' => $this->service->pemasukanKategoriMetode($mulai, $selesai, $unitId),
            'pengeluaran_jenis' => $this->service->pengeluaranPerJenis($mulai, $selesai, $unitId),
            'pengeluaran_item' => $this->service->pengeluaranPerItem($mulai, $selesai, $unitId),
            'rekonsiliasi' => $this->service->rekonsiliasiRingkasan($mulai, $selesai, $unitId),
            'buku_kas' => $bukuKas,
        ];
    }
}
