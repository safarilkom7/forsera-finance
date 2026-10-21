<?php

namespace App\Http\Controllers;

use App\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Penghapusan master data dipisah dari controller CRUD agar satu tempat memuat
 * aturan "hanya boleh hapus data yang belum pernah dipakai".
 */
class MasterDataController extends Controller
{
    public function __construct(private readonly MasterDataService $service) {}

    public function hapus(Request $request, string $jenis, int $id): RedirectResponse
    {
        $model = $this->service->model($jenis);
        $record = $model::query()->findOrFail($id);
        $label = $this->service->label($jenis);
        $nama = $record->getLabel();

        $hasil = $this->service->hapusBanyak($jenis, [$id], $request->user());

        if ($hasil['terhapus'] === 1) {
            return redirect()->route('master.'.$jenis.'.index')
                ->with('sukses', $label.' "'.$nama.'" berhasil dihapus.');
        }

        return redirect()->route('master.'.$jenis.'.index')
            ->with('gagal', $label.' "'.$nama.'" tidak bisa dihapus. '.implode(' ', $hasil['dilewati']));
    }

    public function hapusMassal(Request $request, string $jenis): RedirectResponse
    {
        $label = $this->service->label($jenis);

        $data = $request->validate([
            'id' => ['required', 'array', 'min:1', 'max:200'],
            'id.*' => ['integer'],
        ], [
            'id.required' => 'Centang dulu data yang ingin dihapus.',
            'id.array' => 'Centang dulu data yang ingin dihapus.',
            'id.min' => 'Centang dulu data yang ingin dihapus.',
            'id.max' => 'Maksimal 200 data sekali hapus.',
        ]);

        $hasil = $this->service->hapusBanyak($jenis, $data['id'], $request->user());

        return redirect()->route('master.'.$jenis.'.index')->with(
            ...$this->flash($hasil, $label)
        );
    }

    /**
     * @param  array{terhapus: int, dilewati: array<int, string>}  $hasil
     * @return array{0: string, 1: string}
     */
    private function flash(array $hasil, string $label): array
    {
        // Label tetap berhuruf besar di awal kalimat, tetapi huruf kecil saat
        // disisipkan setelah angka supaya bacaannya wajar.
        $kata = mb_strtolower($label);

        if ($hasil['terhapus'] === 0) {
            return ['gagal', 'Tidak ada '.$kata.' yang dihapus. '.implode(' ', $hasil['dilewati'])];
        }

        $pesan = $hasil['terhapus'].' '.$kata.' dihapus.';

        if ($hasil['dilewati'] !== []) {
            $pesan .= ' '.count($hasil['dilewati']).' dilewati: '.implode(' ', $hasil['dilewati']);
        }

        return ['sukses', $pesan];
    }
}
