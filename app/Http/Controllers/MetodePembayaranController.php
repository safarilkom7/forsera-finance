<?php

namespace App\Http\Controllers;

use App\Enums\TipeMetodePembayaran;
use App\Models\MetodePembayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MetodePembayaranController extends Controller
{
    public function index(): View
    {
        return view('master.metode.index', [
            'data' => MetodePembayaran::query()->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.metode.form', ['metode' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        MetodePembayaran::query()->create($this->validasi($request));

        return redirect()->route('master.metode.index')->with('sukses', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function edit(MetodePembayaran $metode): View
    {
        return view('master.metode.form', ['metode' => $metode]);
    }

    public function update(Request $request, MetodePembayaran $metode): RedirectResponse
    {
        $metode->update($this->validasi($request, $metode));

        return redirect()->route('master.metode.index')->with('sukses', 'Metode pembayaran berhasil diperbarui.');
    }

    private function validasi(Request $request, ?MetodePembayaran $metode = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50', Rule::unique('metode_pembayaran', 'nama')->ignore($metode?->id)],
            'tipe' => ['required', Rule::enum(TipeMetodePembayaran::class)],
            'aktif' => ['boolean'],
        ], [
            'nama.required' => 'Nama metode wajib diisi.',
            'nama.unique' => 'Nama metode sudah dipakai.',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }
}
