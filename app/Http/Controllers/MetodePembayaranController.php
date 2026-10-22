<?php

namespace App\Http\Controllers;

use App\Enums\TipeMetodePembayaran;
use App\Models\MetodePembayaran;
use App\Services\MasterDataService;
use App\Support\FilterTabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MetodePembayaranController extends Controller
{
    public function __construct(private readonly MasterDataService $master) {}

    public function index(Request $request): View
    {
        $peta = [
            'f_nama' => ['kolom' => 'nama'],
            'f_tipe' => ['kolom' => 'tipe', 'jenis' => 'pilih'],
            'f_status' => ['kolom' => 'aktif', 'jenis' => 'aktif'],
        ];

        $filter = FilterTabel::nilai($request, $peta);
        $query = MetodePembayaran::query()->orderBy('nama');
        FilterTabel::terapkan($query, $filter, $peta);
        $data = $query->get();

        return view('master.metode.index', [
            'data' => $data,
            'filter' => $filter,
            'tipeMetodes' => collect(TipeMetodePembayaran::cases())
                ->mapWithKeys(fn (TipeMetodePembayaran $tipe) => [$tipe->value => $tipe->getLabel()])
                ->all(),
            'bolehHapus' => auth()->user()->isOwner(),
            'keterangan' => $this->master->keteranganPemakaian('metode', $data->pluck('id')->all()),
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
