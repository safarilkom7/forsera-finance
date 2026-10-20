<?php

namespace App\Http\Controllers;

use App\Enums\StatusVerifikasi;
use App\Exports\ArrayExport;
use App\Http\Requests\PemasukanRequest;
use App\Models\KategoriPemasukan;
use App\Models\MetodePembayaran;
use App\Models\Pemasukan;
use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\LampiranService;
use App\Services\PemasukanService;
use App\Services\PeriodeService;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PemasukanController extends Controller
{
    public function __construct(
        private readonly PemasukanService $service,
        private readonly LampiranService $lampiran,
        private readonly PeriodeService $periode,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Pemasukan::class);
        $user = $request->user();
        $filter = $this->filterData($request, $user);

        $query = $this->terfilter($filter, $user);
        $total = (int) (clone $query)->sum('nominal');

        $data = $query
            ->with(['unitUsaha', 'kategori', 'metode', 'dibuatOleh'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('pemasukan.index', [
            'data' => $data,
            'filter' => $filter,
            'total' => $total,
            'units' => $this->unitOptions($user),
            'metodes' => MetodePembayaran::query()->orderBy('nama')->get(),
            'kategoris' => KategoriPemasukan::query()->orderBy('nama')->get(),
            'statuses' => StatusVerifikasi::cases(),
            'pembuat' => $user->isStaf()
                ? collect()
                : User::query()->whereIn('id', Pemasukan::query()->select('dibuat_oleh'))->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Pemasukan::class);
        $user = $request->user();
        $units = $this->unitOptions($user);

        $unitDefault = $request->integer('unit');
        if ($unitDefault === 0 || ! $units->contains('id', $unitDefault)) {
            $unitDefault = $user->last_unit_usaha_id && $units->contains('id', $user->last_unit_usaha_id)
                ? $user->last_unit_usaha_id
                : $units->first()?->id;
        }

        return view('pemasukan.form', [
            'pemasukan' => null,
            'units' => $units,
            'metodes' => $this->metodeOptions(),
            'kategoris' => $this->kategoriOptions(),
            'unitDefault' => $unitDefault,
            'tanggalDefault' => now()->toDateString(),
            'terkunci' => false,
            'periodeService' => $this->periode,
        ]);
    }

    public function store(PemasukanRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $meta = $this->lampiran->simpan(
            $request->file('lampiran', []),
            'lampiran/pemasukan',
            $user,
        );

        $data['lampiran'] = $meta;

        try {
            $pemasukan = $this->service->buat($data, $user);
        } catch (ValidationException $exception) {
            $this->lampiran->hapus($meta);

            throw $exception;
        }

        $pesan = 'Pemasukan '.format_rupiah($pemasukan->nominal).' berhasil disimpan.';

        if ($request->boolean('simpan_lagi')) {
            return redirect()
                ->route('pemasukan.create', ['unit' => $pemasukan->unit_usaha_id])
                ->with('sukses', $pesan.' Silakan tambahkan pemasukan berikutnya.');
        }

        return redirect()->route('pemasukan.index')->with('sukses', $pesan);
    }

    public function edit(Request $request, Pemasukan $pemasukan): View
    {
        $this->authorize('update', $pemasukan);
        $pemasukan->load(['unitUsaha', 'kategori', 'metode']);

        return view('pemasukan.form', [
            'pemasukan' => $pemasukan,
            'units' => $this->unitOptions($request->user(), $pemasukan->unit_usaha_id),
            'metodes' => $this->metodeOptions($pemasukan->metode_pembayaran_id),
            'kategoris' => $this->kategoriOptions($pemasukan->kategori_pemasukan_id),
            'unitDefault' => $pemasukan->unit_usaha_id,
            'tanggalDefault' => $pemasukan->tanggal->toDateString(),
            'terkunci' => $pemasukan->terkunci(),
            'periodeService' => $this->periode,
        ]);
    }

    public function update(PemasukanRequest $request, Pemasukan $pemasukan): RedirectResponse
    {
        $this->authorize('update', $pemasukan);
        $user = $request->user();
        $data = $request->validated();

        if ($pemasukan->terkunci()) {
            $this->authorize('koreksi', $pemasukan);

            $this->service->koreksi($pemasukan, $data, (string) $request->input('alasan'), $user);

            return redirect()->route('pemasukan.index')->with('sukses', 'Koreksi data pemasukan berhasil dicatat di audit log.');
        }

        $existing = collect($pemasukan->lampiran ?? []);
        $hapus = (array) $request->input('hapus_lampiran', []);
        $keep = $existing->reject(fn ($item) => in_array($item['path'] ?? null, $hapus, true))->values()->all();

        $baru = $this->lampiran->simpan($request->file('lampiran', []), 'lampiran/pemasukan', $user);

        $data['lampiran'] = array_merge($keep, $baru);
        $data['lampiran_diubah'] = true;

        try {
            $this->service->ubah($pemasukan, $data, $user);
        } catch (ValidationException $exception) {
            $this->lampiran->hapus($baru);

            throw $exception;
        }

        return redirect()->route('pemasukan.index')->with('sukses', 'Perubahan pemasukan berhasil disimpan.');
    }

    public function destroy(Request $request, Pemasukan $pemasukan): RedirectResponse
    {
        $this->authorize('delete', $pemasukan);

        $this->service->hapus($pemasukan, $request->user());

        return redirect()->route('pemasukan.index')->with('sukses', 'Pemasukan berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Pemasukan::class);
        $user = $request->user();
        $filter = $this->filterData($request, $user);

        $rows = $this->terfilter($filter, $user)
            ->with(['unitUsaha', 'kategori', 'metode', 'dibuatOleh'])
            ->orderBy('tanggal')
            ->get()
            ->map(fn (Pemasukan $p) => [
                $p->tanggal->toDateString(),
                $p->unitUsaha->nama,
                $p->kategori->nama,
                $p->metode->nama,
                (int) $p->nominal,
                $p->status_verifikasi->getLabel(),
                $p->note,
                $p->dibuatOleh->name,
            ])
            ->all();

        return Excel::download(
            new ArrayExport('Pemasukan', ['Tanggal', 'Unit', 'Kategori', 'Metode', 'Nominal', 'Status', 'Catatan', 'Dibuat oleh'], $rows),
            'pemasukan-'.$filter['dari'].'-'.$filter['sampai'].'.xlsx',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filterData(Request $request, User $user): array
    {
        $dari = $this->tanggal($request->query('dari'), now()->startOfMonth());
        $sampai = $this->tanggal($request->query('sampai'), now()->endOfMonth());

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $unitId = $request->integer('unit') ?: null;
        if ($unitId !== null) {
            abort_unless($user->bolehAksesUnit($unitId), 403);
        }

        return [
            'dari' => $dari->toDateString(),
            'sampai' => $sampai->toDateString(),
            'unit' => $unitId,
            'metode' => $request->integer('metode') ?: null,
            'kategori' => $request->integer('kategori') ?: null,
            'status' => $request->query('status'),
            'pembuat' => $request->integer('pembuat') ?: null,
            'q' => trim((string) $request->query('q', '')),
        ];
    }

    private function terfilter(array $filter, User $user)
    {
        return Pemasukan::query()
            ->terlihatOleh($user)
            ->whereBetween('tanggal', [$filter['dari'], $filter['sampai']])
            ->when($filter['unit'], fn ($q, $unit) => $q->where('unit_usaha_id', $unit))
            ->when($filter['metode'], fn ($q, $metode) => $q->where('metode_pembayaran_id', $metode))
            ->when($filter['kategori'], fn ($q, $kategori) => $q->where('kategori_pemasukan_id', $kategori))
            ->when($filter['status'], fn ($q, $status) => $q->where('status_verifikasi', $status))
            ->when($filter['pembuat'], fn ($q, $pembuat) => $q->where('dibuat_oleh', $pembuat))
            ->when($filter['q'] !== '', fn ($q) => $q->where('note', 'like', '%'.$filter['q'].'%'));
    }

    private function tanggal(?string $nilai, Carbon $default): Carbon
    {
        if ($nilai === null || $nilai === '') {
            return $default->copy();
        }

        try {
            return Carbon::parse($nilai);
        } catch (\Throwable) {
            return $default->copy();
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, UnitUsaha>
     */
    private function unitOptions(User $user, ?int $termasuk = null)
    {
        return UnitUsaha::query()
            ->where(function ($q) use ($user, $termasuk) {
                $q->where('aktif', true);

                if ($termasuk !== null) {
                    $q->orWhere('id', $termasuk);
                }
            })
            ->when($user->isStaf(), fn ($q) => $q->whereIn('id', $user->unitUsaha()->select('unit_usaha.id')))
            ->orderBy('id')
            ->get();
    }

    private function metodeOptions(?int $termasuk = null)
    {
        return MetodePembayaran::query()
            ->where(fn ($q) => $q->where('aktif', true)->when($termasuk, fn ($r) => $r->orWhere('id', $termasuk)))
            ->orderBy('nama')
            ->get();
    }

    private function kategoriOptions(?int $termasuk = null)
    {
        return KategoriPemasukan::query()
            ->where(fn ($q) => $q->where('aktif', true)->when($termasuk, fn ($r) => $r->orWhere('id', $termasuk)))
            ->orderBy('id')
            ->get();
    }
}
