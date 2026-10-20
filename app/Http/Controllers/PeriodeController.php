<?php

namespace App\Http\Controllers;

use App\Models\PeriodeTutup;
use App\Models\UnitUsaha;
use App\Services\PeriodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodeController extends Controller
{
    public function __construct(private readonly PeriodeService $service) {}

    public function index(): View
    {
        return view('periode.index', [
            'data' => PeriodeTutup::query()
                ->with(['unitUsaha', 'ditutupOleh', 'dibukaKembaliOleh'])
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->paginate(20),
            'units' => UnitUsaha::query()->orderBy('nama')->get(),
            'sekarang' => now(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'unit_usaha_id' => ['nullable', 'integer', 'exists:unit_usaha,id'],
        ], [
            'tahun.required' => 'Tahun wajib diisi.',
            'bulan.required' => 'Bulan wajib dipilih.',
        ]);

        $this->service->tutup(
            (int) $data['tahun'],
            (int) $data['bulan'],
            $data['unit_usaha_id'] ? (int) $data['unit_usaha_id'] : null,
            $request->user(),
        );

        return redirect()->route('periode.index')->with('sukses', 'Periode berhasil ditutup. Transaksi pada periode ini tidak dapat diubah.');
    }

    public function buka(Request $request, PeriodeTutup $periode): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'alasan.required' => 'Alasan wajib diisi untuk membuka kembali periode.',
            'alasan.min' => 'Alasan minimal 5 karakter.',
        ]);

        $this->service->bukaKembali($periode, $data['alasan'], $request->user());

        return redirect()->route('periode.index')->with('sukses', 'Periode dibuka kembali. Perubahan sudah tercatat di audit log.');
    }
}
