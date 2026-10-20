<?php

namespace App\Http\Controllers;

use App\Enums\TipeUnitUsaha;
use App\Models\UnitUsaha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitUsahaController extends Controller
{
    public function index(): View
    {
        return view('master.unit.index', [
            'data' => UnitUsaha::query()->withCount(['pemasukan', 'pengeluaran'])->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.unit.form', ['unit' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        UnitUsaha::query()->create($this->validasi($request));

        return redirect()->route('master.unit.index')->with('sukses', 'Unit usaha berhasil ditambahkan.');
    }

    public function edit(UnitUsaha $unit): View
    {
        return view('master.unit.form', ['unit' => $unit]);
    }

    public function update(Request $request, UnitUsaha $unit): RedirectResponse
    {
        $unit->update($this->validasi($request, $unit));

        return redirect()->route('master.unit.index')->with('sukses', 'Unit usaha berhasil diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?UnitUsaha $unit = null): array
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'alpha_num', Rule::unique('unit_usaha', 'kode')->ignore($unit?->id)],
            'nama' => ['required', 'string', 'max:100'],
            'tipe' => ['required', Rule::enum(TipeUnitUsaha::class)],
            'aktif' => ['boolean'],
        ], [
            'kode.required' => 'Kode unit wajib diisi.',
            'kode.unique' => 'Kode unit sudah dipakai.',
            'nama.required' => 'Nama unit wajib diisi.',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }
}
