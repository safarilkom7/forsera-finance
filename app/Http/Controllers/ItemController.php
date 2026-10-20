<?php

namespace App\Http\Controllers;

use App\Models\DetailPengeluaran;
use App\Models\Item;
use App\Models\JenisPengeluaran;
use App\Models\Satuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(): View
    {
        return view('master.item.index', [
            'data' => Item::query()->with(['satuanDefault', 'jenisPengeluaran'])->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('master.item.form', $this->opsi(null));
    }

    public function store(Request $request): RedirectResponse
    {
        Item::query()->create($this->validasi($request));

        return redirect()->route('master.item.index')->with('sukses', 'Item berhasil ditambahkan.');
    }

    public function edit(Item $item): View
    {
        $riwayat = DetailPengeluaran::query()
            ->where('item_id', $item->id)
            ->with(['pengeluaran.unitUsaha', 'satuan'])
            ->latest('id')
            ->limit(20)
            ->get();

        return view('master.item.form', $this->opsi($item) + [
            'riwayat' => $riwayat,
            'rataRata' => $item->rataRataLimaTerakhir(),
        ]);
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $item->update($this->validasi($request, $item));

        return redirect()->route('master.item.index')->with('sukses', 'Item berhasil diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opsi(?Item $item): array
    {
        return [
            'item' => $item,
            'satuans' => Satuan::query()->orderBy('nama')->get(),
            'jenises' => JenisPengeluaran::query()->orderBy('nama')->get(),
        ];
    }

    private function validasi(Request $request, ?Item $item = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('item', 'nama')->ignore($item?->id)],
            'satuan_default_id' => ['required', 'integer', 'exists:satuan,id'],
            'jenis_pengeluaran_id' => ['nullable', 'integer', 'exists:jenis_pengeluaran,id'],
            'harga_terakhir' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ], [
            'nama.required' => 'Nama item wajib diisi.',
            'nama.unique' => 'Nama item sudah dipakai.',
            'satuan_default_id.required' => 'Satuan default wajib dipilih.',
        ]);

        $data['harga_terakhir'] = $request->filled('harga_terakhir') ? parse_rupiah($data['harga_terakhir']) : null;
        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }
}
