<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use App\Services\MasterDataService;
use App\Support\FilterTabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SatuanController extends Controller
{
    public function __construct(private readonly MasterDataService $master) {}

    public function index(Request $request): View
    {
        $peta = [
            'f_nama' => ['kolom' => 'nama'],
            'f_singkatan' => ['kolom' => 'singkatan'],
        ];

        $filter = FilterTabel::nilai($request, $peta);
        $query = Satuan::query()->orderBy('nama');
        FilterTabel::terapkan($query, $filter, $peta);
        $data = $query->get();

        return view('master.satuan.index', [
            'data' => $data,
            'filter' => $filter,
            'bolehHapus' => auth()->user()->isOwner(),
            'keterangan' => $this->master->keteranganPemakaian('satuan', $data->pluck('id')->all()),
        ]);
    }

    public function create(): View
    {
        return view('master.satuan.form', ['satuan' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Satuan::query()->create($this->validasi($request));

        return redirect()->route('master.satuan.index')->with('sukses', 'Satuan berhasil ditambahkan.');
    }

    public function edit(Satuan $satuan): View
    {
        return view('master.satuan.form', ['satuan' => $satuan]);
    }

    public function update(Request $request, Satuan $satuan): RedirectResponse
    {
        $satuan->update($this->validasi($request, $satuan));

        return redirect()->route('master.satuan.index')->with('sukses', 'Satuan berhasil diperbarui.');
    }

    private function validasi(Request $request, ?Satuan $satuan = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:50', Rule::unique('satuan', 'nama')->ignore($satuan?->id)],
            'singkatan' => ['required', 'string', 'max:20'],
        ], [
            'nama.required' => 'Nama satuan wajib diisi.',
            'nama.unique' => 'Nama satuan sudah dipakai.',
            'singkatan.required' => 'Singkatan satuan wajib diisi.',
        ]);
    }
}
