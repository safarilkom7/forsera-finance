<?php

namespace App\Http\Controllers;

use App\Models\KategoriPemasukan;
use App\Models\UnitUsaha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategoriPemasukanController extends Controller
{
    public function index(): View
    {
        return view('master.kategori.index', [
            'data' => KategoriPemasukan::query()->with('unitUsaha')->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.kategori.form', [
            'kategori' => null,
            'units' => UnitUsaha::query()->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        KategoriPemasukan::query()->create($this->validasi($request));

        return redirect()->route('master.kategori.index')->with('sukses', 'Kategori pemasukan berhasil ditambahkan.');
    }

    public function edit(KategoriPemasukan $kategori): View
    {
        return view('master.kategori.form', [
            'kategori' => $kategori,
            'units' => UnitUsaha::query()->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, KategoriPemasukan $kategori): RedirectResponse
    {
        $kategori->update($this->validasi($request));

        return redirect()->route('master.kategori.index')->with('sukses', 'Kategori pemasukan berhasil diperbarui.');
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'unit_usaha_id' => ['nullable', 'integer', 'exists:unit_usaha,id'],
            'aktif' => ['boolean'],
        ], [
            'nama.required' => 'Nama kategori wajib diisi.',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }
}
