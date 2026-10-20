<?php

namespace App\Http\Controllers;

use App\Enums\StatusRekonsiliasi;
use App\Http\Requests\RekonsiliasiRequest;
use App\Models\MetodePembayaran;
use App\Models\Rekonsiliasi;
use App\Models\UnitUsaha;
use App\Services\LampiranService;
use App\Services\PeriodeAkuntansiService;
use App\Services\RekonsiliasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RekonsiliasiController extends Controller
{
    public function __construct(
        private readonly RekonsiliasiService $service,
        private readonly LampiranService $lampiran,
        private readonly PeriodeAkuntansiService $akuntansi,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Rekonsiliasi::class);

        $periodeBerjalan = $this->akuntansi->berjalan();
        $dari = $this->tanggal($request->query('dari'), $periodeBerjalan['mulai']);
        $sampai = $this->tanggal($request->query('sampai'), $periodeBerjalan['selesai']);
        $unitId = $request->integer('unit') ?: null;

        if ($unitId !== null) {
            abort_unless($request->user()->bolehAksesUnit($unitId), 403);
        }

        $query = Rekonsiliasi::query()
            ->with(['unitUsaha', 'metode', 'diverifikasiOleh'])
            ->whereBetween('periode_mulai', [$dari->toDateString(), $sampai->toDateString()])
            ->when($unitId, fn ($q) => $q->where('unit_usaha_id', $unitId))
            ->when($request->integer('metode'), fn ($q, $m) => $q->where('metode_pembayaran_id', $m))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s));

        return view('rekonsiliasi.index', [
            'data' => $query->orderByDesc('periode_mulai')->orderByDesc('id')->paginate(20)->withQueryString(),
            'units' => $this->units(),
            'metodes' => MetodePembayaran::query()->orderBy('nama')->get(),
            'statuses' => StatusRekonsiliasi::cases(),
            'filter' => [
                'dari' => $dari->toDateString(),
                'sampai' => $sampai->toDateString(),
                'unit' => $unitId,
                'metode' => $request->integer('metode') ?: null,
                'status' => $request->query('status'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Rekonsiliasi::class);
        $units = $this->units();

        $unitId = $request->integer('unit') ?: null;
        $metodeId = $request->integer('metode') ?: null;
        $mulai = $request->query('mulai');
        $selesai = $request->query('selesai');

        $siap = $unitId !== null && $metodeId !== null && $mulai !== null && $selesai !== null;

        $kandidat = collect();
        $potongan = 0;
        $saran = 0;
        $unitTerpilih = null;
        $periodeBerjalan = $this->akuntansi->berjalan();

        if ($siap) {
            abort_unless($request->user()->bolehAksesUnit($unitId), 403);

            $mulaiCarbon = $this->tanggal($mulai, $periodeBerjalan['mulai']);
            $selesaiCarbon = $this->tanggal($selesai, $periodeBerjalan['selesai']);

            if ($mulaiCarbon->gt($selesaiCarbon)) {
                [$mulaiCarbon, $selesaiCarbon] = [$selesaiCarbon, $mulaiCarbon];
            }

            $kandidat = $this->service->kandidat($unitId, $metodeId, $mulaiCarbon->toDateString(), $selesaiCarbon->toDateString());
            $potongan = $this->service->potonganKas($unitId, $metodeId, $mulaiCarbon->toDateString(), $selesaiCarbon->toDateString());
            $saran = $this->service->saranBiayaAdmin((int) $kandidat->sum('nominal'), $metodeId);
            $unitTerpilih = $units->firstWhere('id', $unitId);

            $mulai = $mulaiCarbon->toDateString();
            $selesai = $selesaiCarbon->toDateString();
        }

        return view('rekonsiliasi.create', [
            'langkah' => $siap ? 2 : 1,
            'units' => $units,
            'metodes' => MetodePembayaran::query()->where('aktif', true)->orderBy('nama')->get(),
            'unitId' => $unitId,
            'metodeId' => $metodeId,
            'mulai' => $mulai ? $this->tanggal($mulai, $periodeBerjalan['mulai'])->toDateString() : $periodeBerjalan['mulai']->toDateString(),
            'selesai' => $selesai
                ? $this->tanggal($selesai, $periodeBerjalan['selesai'])->toDateString()
                : ($periodeBerjalan['selesai']->isFuture() ? now()->toDateString() : $periodeBerjalan['selesai']->toDateString()),
            'kandidat' => $kandidat,
            'potongan' => $potongan,
            'saran' => $saran,
            'unitTerpilih' => $unitTerpilih,
            'metodeTerpilih' => $metodeId ? MetodePembayaran::query()->find($metodeId) : null,
            'toleransi' => app(\App\Services\PengaturanService::class)->getInt('toleransi_selisih', 1000),
            'mdr' => app(\App\Services\PengaturanService::class)->getDecimal('mdr_qris_persen', 0.7),
        ]);
    }

    public function store(RekonsiliasiRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $meta = $this->lampiran->simpan($request->file('bukti', []), 'lampiran/rekonsiliasi', $user);
        $data['bukti'] = $meta;

        try {
            $hasil = $this->service->simpan($data, $user);
        } catch (ValidationException $exception) {
            $this->lampiran->hapus($meta);

            throw $exception;
        }

        if ($hasil['menunggu']) {
            return redirect()->route('rekonsiliasi.index')
                ->with('sukses', 'Pemasukan ditandai menunggu settlement. Tidak ada insiden yang dibuat.');
        }

        $rekonsiliasi = $hasil['rekonsiliasi'];

        $pesan = 'Rekonsiliasi '.$rekonsiliasi->kode.' disimpan dengan status '.$rekonsiliasi->status->getLabel().'.';

        if ($hasil['insiden'] !== null) {
            $pesan .= ' Insiden '.$hasil['insiden']->kode.' dibuat otomatis.';
        }

        return redirect()->route('rekonsiliasi.show', $rekonsiliasi)->with('sukses', $pesan);
    }

    public function show(Request $request, Rekonsiliasi $rekonsiliasi): View
    {
        $this->authorize('view', $rekonsiliasi);

        $rekonsiliasi->load([
            'unitUsaha',
            'metode',
            'diverifikasiOleh',
            'dibatalkanOleh',
            'pemasukan.kategori',
            'pemasukan.dibuatOleh',
            'potonganPengeluaran.jenis',
            'insiden.penanggungJawab',
        ]);

        return view('rekonsiliasi.show', [
            'rekonsiliasi' => $rekonsiliasi,
        ]);
    }

    public function batal(Request $request, Rekonsiliasi $rekonsiliasi): RedirectResponse
    {
        $this->authorize('batal', $rekonsiliasi);

        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        $this->service->batal($rekonsiliasi, $data['alasan'], $request->user());

        return redirect()->route('rekonsiliasi.show', $rekonsiliasi)
            ->with('sukses', 'Rekonsiliasi dibatalkan. Pemasukan dan pengeluaran terkait sudah dikembalikan.');
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

    private function units()
    {
        return UnitUsaha::query()->where('aktif', true)->orderBy('nama')->get();
    }
}
