<?php

namespace App\Http\Controllers;

use App\Services\PengaturanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function __construct(private readonly PengaturanService $service) {}

    public function index(): View
    {
        return view('pengaturan.index', [
            'defaults' => PengaturanService::DEFAULTS,
            'nilai' => $this->service->semua(),
            'service' => $this->service,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $aturan = [];

        foreach (PengaturanService::DEFAULTS as $key => $meta) {
            $aturan[$key] = $meta['tipe'] === 'decimal'
                ? ['required', 'numeric', 'min:0', 'max:100']
                : ['required', 'integer', 'min:0'];
        }

        $data = $request->validate($aturan, [
            'required' => 'Semua pengaturan wajib diisi.',
            'integer' => 'Nilai pengaturan harus berupa angka bulat.',
            'numeric' => 'Nilai pengaturan harus berupa angka.',
        ]);

        foreach ($data as $key => $value) {
            $this->service->set($key, $value);
        }

        return redirect()->route('pengaturan.index')->with('sukses', 'Pengaturan berhasil disimpan.');
    }
}
