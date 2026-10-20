<?php

namespace App\Http\Controllers;

use App\Services\BrandingService;
use App\Services\PengaturanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function __construct(
        private readonly PengaturanService $service,
        private readonly BrandingService $branding,
    ) {}

    public function index(): View
    {
        return view('pengaturan.index', [
            'defaults' => PengaturanService::DEFAULTS,
            'nilai' => $this->service->semua(),
            'service' => $this->service,
            'branding' => $this->branding->semua(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $aturan = [];

        foreach (PengaturanService::DEFAULTS as $key => $meta) {
            $aturan[$key] = $meta['tipe'] === 'decimal'
                ? ['required', 'numeric', 'min:'.($meta['min'] ?? 0), 'max:'.($meta['max'] ?? 100)]
                : ['required', 'integer', 'min:'.($meta['min'] ?? 0), 'max:'.($meta['max'] ?? 999999999999)];
        }

        $data = $request->validate($aturan, [
            'required' => 'Semua pengaturan wajib diisi.',
            'integer' => 'Nilai pengaturan harus berupa angka bulat.',
            'numeric' => 'Nilai pengaturan harus berupa angka.',
            'min' => 'Nilai pengaturan terlalu kecil.',
            'max' => 'Nilai pengaturan terlalu besar (maksimal :max).',
        ]);

        foreach ($data as $key => $value) {
            $this->service->set($key, $value);
        }

        return redirect()->route('pengaturan.index')->with('sukses', 'Pengaturan berhasil disimpan.');
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_nama' => ['required', 'string', 'max:60'],
            'app_brand' => ['required', 'string', 'max:60'],
            'app_title' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:'.BrandingService::LOGO_MAX_KB],
            'hapus_logo' => ['nullable', 'boolean'],
        ], [
            'app_nama.required' => 'Nama aplikasi wajib diisi.',
            'app_brand.required' => 'Nama brand wajib diisi.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat PNG, JPG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
        ]);

        $this->branding->simpanIdentitas([
            'nama' => $data['app_nama'],
            'brand' => $data['app_brand'],
            'title' => $data['app_title'] ?? '',
        ]);

        if ($request->hasFile('logo')) {
            $this->branding->simpanLogo($request->file('logo'));
        } elseif ($request->boolean('hapus_logo')) {
            $this->branding->hapusLogo();
        }

        activity('pengaturan')
            ->causedBy($request->user())
            ->withProperties([
                'nama' => $data['app_nama'],
                'brand' => $data['app_brand'],
                'title' => $data['app_title'] ?? '',
                'logo_diubah' => $request->hasFile('logo') || $request->boolean('hapus_logo'),
                'ip' => $request->ip(),
            ])
            ->log('Mengubah identitas aplikasi');

        return redirect()->route('pengaturan.index')->with('sukses', 'Identitas aplikasi berhasil disimpan.');
    }
}
