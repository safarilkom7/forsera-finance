<?php

namespace App\Http\Controllers;

use App\Models\JenisPengeluaran;
use App\Services\MasterDataService;
use App\Support\FilterTabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JenisPengeluaranController extends Controller
{
    public function __construct(private readonly MasterDataService $master) {}

    public function index(Request $request): View
    {
        $peta = [
            'f_nama' => ['kolom' => 'nama'],
            'f_status' => ['kolom' => 'aktif', 'jenis' => 'aktif'],
        ];

        $filter = FilterTabel::nilai($request, $peta);
        $query = JenisPengeluaran::query()->orderBy('nama');
        FilterTabel::terapkan($query, $filter, $peta);
        $data = $query->get();

        return view('master.jenis.index', [
            'data' => $data,
            'filter' => $filter,
            'bolehHapus' => auth()->user()->isOwner(),
            'keterangan' => $this->master->keteranganPemakaian('jenis', $data->pluck('id')->all()),
        ]);
    }

    public function create(): View
    {
        return view('master.jenis.form', ['jenis' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        JenisPengeluaran::query()->create($this->validasi($request));

        return redirect()->route('master.jenis.index')->with('sukses', 'Jenis pengeluaran berhasil ditambahkan.');
    }

    public function edit(JenisPengeluaran $jenis): View
    {
        return view('master.jenis.form', ['jenis' => $jenis]);
    }

    public function update(Request $request, JenisPengeluaran $jenis): RedirectResponse
    {
        $jenis->update($this->validasi($request, $jenis));

        return redirect()->route('master.jenis.index')->with('sukses', 'Jenis pengeluaran berhasil diperbarui.');
    }

    private function validasi(Request $request, ?JenisPengeluaran $jenis = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('jenis_pengeluaran', 'nama')->ignore($jenis?->id)],
            'aktif' => ['boolean'],
        ], [
            'nama.required' => 'Nama jenis pengeluaran wajib diisi.',
            'nama.unique' => 'Nama jenis pengeluaran sudah dipakai.',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }
}
