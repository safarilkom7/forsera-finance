<?php

namespace App\Http\Controllers;

use App\Enums\JenisInsiden;
use App\Enums\StatusInsiden;
use App\Enums\TipePenyelesaian;
use App\Models\Insiden;
use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\InsidenService;
use App\Services\LampiranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InsidenController extends Controller
{
    public function __construct(
        private readonly InsidenService $service,
        private readonly LampiranService $lampiran,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Insiden::class);

        $query = Insiden::query()
            ->with(['rekonsiliasi.unitUsaha', 'rekonsiliasi.metode', 'penanggungJawab'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('jenis'), fn ($q, $jenis) => $q->where('jenis', $jenis))
            ->when($request->query('arah'), fn ($q, $arah) => $q->where('arah', $arah))
            ->when($request->integer('unit'), fn ($q, $unit) => $q->whereHas('rekonsiliasi', fn ($r) => $r->where('unit_usaha_id', $unit)))
            ->when(trim((string) $request->query('q')) !== '', fn ($q) => $q->where('kode', 'like', '%'.$request->query('q').'%'));

        return view('insiden.index', [
            'data' => $query->orderByDesc('created_at')->paginate(20)->withQueryString(),
            'units' => UnitUsaha::query()->orderBy('nama')->get(),
            'statuses' => StatusInsiden::cases(),
            'jenises' => JenisInsiden::cases(),
            'filter' => [
                'status' => $request->query('status'),
                'jenis' => $request->query('jenis'),
                'arah' => $request->query('arah'),
                'unit' => $request->integer('unit') ?: null,
                'q' => (string) $request->query('q', ''),
            ],
        ]);
    }

    public function show(Request $request, Insiden $insiden): View
    {
        $this->authorize('view', $insiden);

        $insiden->load([
            'rekonsiliasi.unitUsaha',
            'rekonsiliasi.metode',
            'rekonsiliasi.pemasukan',
            'penanggungJawab',
            'dibuatOleh',
            'ditutupOleh',
            'log.user',
            'pemasukanLain',
        ]);

        return view('insiden.show', [
            'insiden' => $insiden,
            'pengguna' => User::query()->where('aktif', true)->orderBy('name')->get(),
            'tipePenyelesaian' => TipePenyelesaian::cases(),
        ]);
    }

    public function investigasi(Request $request, Insiden $insiden): RedirectResponse
    {
        $this->authorize('kelola', $insiden);

        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->mulaiInvestigasi($insiden, $request->user(), $data['catatan'] ?? null);

        return back()->with('sukses', 'Insiden masuk tahap investigasi.');
    }

    public function tugaskan(Request $request, Insiden $insiden): RedirectResponse
    {
        $this->authorize('kelola', $insiden);

        $data = $request->validate([
            'penanggung_jawab_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $this->service->tugaskan(
            $insiden,
            $data['penanggung_jawab_id'] ? User::query()->find($data['penanggung_jawab_id']) : null,
            $request->user(),
        );

        return back()->with('sukses', 'Penanggung jawab diperbarui.');
    }

    public function selesaikan(Request $request, Insiden $insiden): RedirectResponse
    {
        $this->authorize('kelola', $insiden);

        $files = $request->file('lampiran', []);
        $files = is_array($files) ? array_values(array_filter($files)) : [];
        $request->merge([
            'nominal' => parse_rupiah($request->input('nominal', 0)),
            'lampiran' => $files === [] ? null : $files,
        ]);

        $data = $request->validate([
            'tipe_penyelesaian' => ['required', Rule::enum(TipePenyelesaian::class)],
            'penyebab' => ['required', 'string', 'max:1000'],
            'catatan_penyelesaian' => ['required', 'string', 'max:2000'],
            'nominal' => ['nullable', 'integer', 'min:0'],
            'lampiran' => ['nullable', 'array', 'max:5'],
            'lampiran.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'tipe_penyelesaian.required' => 'Tipe penyelesaian wajib dipilih.',
            'penyebab.required' => 'Penyebab wajib diisi.',
            'catatan_penyelesaian.required' => 'Catatan penyelesaian wajib diisi.',
            'lampiran.*.mimes' => 'Bukti harus berupa JPG, PNG, WEBP, atau PDF.',
            'lampiran.*.max' => 'Ukuran setiap bukti maksimal 5 MB.',
        ]);

        $meta = $this->lampiran->simpan($request->file('lampiran', []), 'lampiran/insiden', $request->user());
        $data['lampiran'] = $meta;

        try {
            $this->service->selesaikan($insiden, $data, $request->user());
        } catch (ValidationException $exception) {
            $this->lampiran->hapus($meta);

            throw $exception;
        }

        return back()->with('sukses', 'Penyelesaian insiden dicatat pada timeline.');
    }
}
