<?php

namespace App\Http\Controllers;

use App\Models\DetailPengeluaran;
use App\Models\Item;
use App\Models\JenisPengeluaran;
use App\Models\Satuan;
use App\Services\MasterDataService;
use App\Support\FilterTabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function __construct(private readonly MasterDataService $master) {}

    public function index(Request $request): View
    {
        $peta = [
            'f_nama' => ['kolom' => 'nama'],
            'f_satuan' => ['kolom' => 'satuan_default_id', 'jenis' => 'pilih'],
            'f_jenis' => ['kolom' => 'jenis_pengeluaran_id', 'jenis' => 'pilih', 'null' => 'tanpa'],
            'f_harga' => ['kolom' => 'harga_terakhir', 'jenis' => 'isi'],
            'f_status' => ['kolom' => 'aktif', 'jenis' => 'aktif'],
        ];

        $filter = FilterTabel::nilai($request, $peta);
        $query = Item::query()->with(['satuanDefault', 'jenisPengeluaran'])->orderBy('nama');
        FilterTabel::terapkan($query, $filter, $peta);
        $data = $query->get();

        return view('master.item.index', [
            'data' => $data,
            'filter' => $filter,
            'satuans' => Satuan::query()->orderBy('nama')->get(),
            'jenises' => JenisPengeluaran::query()->orderBy('nama')->get(),
            'bolehHapus' => auth()->user()->isOwner(),
            'keterangan' => $this->master->keteranganPemakaian('item', $data->pluck('id')->all()),
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
