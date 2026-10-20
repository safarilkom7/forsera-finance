<?php

namespace App\Http\Controllers;

use App\Enums\StatusPersetujuanPengeluaran;
use App\Exports\ArrayExport;
use App\Http\Requests\PengeluaranRequest;
use App\Models\Item;
use App\Models\JenisPengeluaran;
use App\Models\MetodePembayaran;
use App\Models\Pengeluaran;
use App\Models\Satuan;
use App\Models\UnitUsaha;
use App\Models\User;
use App\Services\LampiranService;
use App\Services\NotifikasiService;
use App\Services\PengeluaranService;
use App\Services\PeriodeAkuntansiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PengeluaranController extends Controller
{
    public function __construct(
        private readonly PengeluaranService $service,
        private readonly LampiranService $lampiran,
        private readonly NotifikasiService $notifikasi,
        private readonly PeriodeAkuntansiService $akuntansi,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Pengeluaran::class);
        $user = $request->user();
        $filter = $this->filterData($request, $user);

        $query = $this->terfilter($filter, $user);
        $total = (int) (clone $query)->sum('total_nominal');

        $data = $query
            ->with(['unitUsaha', 'jenis', 'metode', 'dibuatOleh'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('pengeluaran.index', [
            'data' => $data,
            'filter' => $filter,
            'total' => $total,
            'units' => $this->unitOptions($user),
            'metodes' => MetodePembayaran::query()->orderBy('nama')->get(),
            'jenises' => JenisPengeluaran::query()->orderBy('nama')->get(),
            'statuses' => StatusPersetujuanPengeluaran::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Pengeluaran::class);
        $user = $request->user();
        $units = $this->unitOptions($user);

        $unitDefault = $request->integer('unit');
        if ($unitDefault === 0 || ! $units->contains('id', $unitDefault)) {
            $unitDefault = $user->last_unit_usaha_id && $units->contains('id', $user->last_unit_usaha_id)
                ? $user->last_unit_usaha_id
                : $units->first()?->id;
        }

        return view('pengeluaran.form', [
            'pengeluaran' => null,
            'units' => $units,
            'metodes' => $this->metodeOptions(),
            'jenises' => JenisPengeluaran::query()->where('aktif', true)->orderBy('nama')->get(),
            'items' => Item::query()->where('aktif', true)->orderBy('nama')->get(),
            'satuans' => Satuan::query()->orderBy('nama')->get(),
            'unitDefault' => $unitDefault,
            'tanggalDefault' => now()->toDateString(),
        ]);
    }

    public function store(PengeluaranRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $meta = $this->lampiran->simpan($request->file('lampiran', []), 'lampiran/pengeluaran', $user);
        $data['lampiran'] = $meta;

        try {
            $pengeluaran = $this->service->buat($data, $user);
        } catch (ValidationException $exception) {
            $this->lampiran->hapus($meta);

            throw $exception;
        }

        if ($pengeluaran->butuhPersetujuan()) {
            $this->notifikasi->pengeluaranMenunggu($pengeluaran);
        }

        $pesan = $pengeluaran->butuhPersetujuan()
            ? 'Pengeluaran disimpan dan menunggu persetujuan Owner.'
            : 'Pengeluaran '.format_rupiah($pengeluaran->total_nominal).' berhasil disimpan.';

        if ($request->boolean('simpan_lagi')) {
            return redirect()->route('pengeluaran.create', ['unit' => $pengeluaran->unit_usaha_id])->with('sukses', $pesan);
        }

        return redirect()->route('pengeluaran.index')->with('sukses', $pesan);
    }

    public function edit(Request $request, Pengeluaran $pengeluaran): View
    {
        $this->authorize('update', $pengeluaran);
        $pengeluaran->load(['detail.item', 'detail.satuan', 'metode', 'jenis']);

        return view('pengeluaran.form', [
            'pengeluaran' => $pengeluaran,
            'units' => $this->unitOptions($request->user(), $pengeluaran->unit_usaha_id),
            'metodes' => $this->metodeOptions($pengeluaran->metode_pembayaran_id),
            'jenises' => JenisPengeluaran::query()
                ->where(fn ($q) => $q->where('aktif', true)->orWhere('id', $pengeluaran->jenis_pengeluaran_id))
                ->orderBy('nama')
                ->get(),
            'items' => Item::query()
                ->where(fn ($q) => $q->where('aktif', true)->orWhereIn('id', $pengeluaran->detail->pluck('item_id')))
                ->orderBy('nama')
                ->get(),
            'satuans' => Satuan::query()->orderBy('nama')->get(),
            'unitDefault' => $pengeluaran->unit_usaha_id,
            'tanggalDefault' => $pengeluaran->tanggal->toDateString(),
        ]);
    }

    public function update(PengeluaranRequest $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->authorize('update', $pengeluaran);
        $user = $request->user();

        $this->authorize('update', $pengeluaran);

        $data = $request->validated();

        $existing = collect($pengeluaran->lampiran ?? []);
        $hapus = (array) $request->input('hapus_lampiran', []);
        $keep = $existing->reject(fn ($item) => in_array($item['path'] ?? null, $hapus, true))->values()->all();
        $baru = $this->lampiran->simpan($request->file('lampiran', []), 'lampiran/pengeluaran', $user);

        $data['lampiran'] = array_merge($keep, $baru);
        $data['lampiran_diubah'] = true;

        try {
            $this->service->ubah($pengeluaran, $data, $user);
        } catch (ValidationException $exception) {
            $this->lampiran->hapus($baru);

            throw $exception;
        }

        return redirect()->route('pengeluaran.index')->with('sukses', 'Perubahan pengeluaran berhasil disimpan.');
    }

    public function destroy(Request $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->authorize('delete', $pengeluaran);

        $this->service->hapus($pengeluaran, $request->user());

        return redirect()->route('pengeluaran.index')->with('sukses', 'Pengeluaran berhasil dihapus.');
    }

    public function setujui(Request $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->authorize('setujui', $pengeluaran);

        $this->service->setujui($pengeluaran, $request->user());

        return redirect()->route('pengeluaran.index')->with('sukses', 'Pengeluaran disetujui.');
    }

    public function tolak(Request $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->authorize('setujui', $pengeluaran);

        $this->service->tolak($pengeluaran, $request->user());

        return redirect()->route('pengeluaran.index')->with('sukses', 'Pengeluaran ditolak.');
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Pengeluaran::class);
        $user = $request->user();
        $filter = $this->filterData($request, $user);

        $rows = $this->terfilter($filter, $user)
            ->with(['unitUsaha', 'jenis', 'metode', 'dibuatOleh'])
            ->orderBy('tanggal')
            ->get()
            ->map(fn (Pengeluaran $p) => [
                $p->tanggal->toDateString(),
                $p->unitUsaha->nama,
                $p->jenis->nama,
                $p->metode->nama,
                (int) $p->total_nominal,
                $p->status_persetujuan->getLabel(),
                $p->dari_kas_harian ? 'Ya' : 'Tidak',
                $p->note,
                $p->dibuatOleh->name,
            ])
            ->all();

        return Excel::download(
            new ArrayExport('Pengeluaran', ['Tanggal', 'Unit', 'Jenis', 'Metode', 'Total', 'Status', 'Dari Kas', 'Catatan', 'Dibuat oleh'], $rows),
            'pengeluaran-'.$filter['dari'].'-'.$filter['sampai'].'.xlsx',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filterData(Request $request, User $user): array
    {
        $periodeBerjalan = $this->akuntansi->berjalan();
        $dari = $this->tanggal($request->query('dari'), $periodeBerjalan['mulai']);
        $sampai = $this->tanggal($request->query('sampai'), $periodeBerjalan['selesai']);

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
            'jenis' => $request->integer('jenis') ?: null,
            'metode' => $request->integer('metode') ?: null,
            'status' => $request->query('status'),
            'q' => trim((string) $request->query('q', '')),
        ];
    }

    private function terfilter(array $filter, User $user)
    {
        return Pengeluaran::query()
            ->terlihatOleh($user)
            ->whereBetween('tanggal', [$filter['dari'], $filter['sampai']])
            ->when($filter['unit'], fn ($q, $unit) => $q->where('unit_usaha_id', $unit))
            ->when($filter['jenis'], fn ($q, $jenis) => $q->where('jenis_pengeluaran_id', $jenis))
            ->when($filter['metode'], fn ($q, $metode) => $q->where('metode_pembayaran_id', $metode))
            ->when($filter['status'], fn ($q, $status) => $q->where('status_persetujuan', $status))
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

    private function unitOptions(User $user, ?int $termasuk = null)
    {
        return UnitUsaha::query()
            ->where(function ($q) use ($termasuk) {
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
            ->orderBy('id')
            ->get();
    }
}
