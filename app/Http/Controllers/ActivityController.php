<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $dari = $this->tanggal($request->query('dari'), now()->subDays(30));
        $sampai = $this->tanggal($request->query('sampai'), now());

        $data = Activity::query()
            ->with('causer')
            ->whereBetween('created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->when($request->query('log'), fn ($q, $log) => $q->where('log_name', $log))
            ->when($request->query('causer'), fn ($q, $causer) => $q->where('causer_id', $causer))
            ->when(trim((string) $request->query('q')) !== '', function ($q) use ($request) {
                $q->where(fn ($r) => $r
                    ->where('description', 'like', '%'.$request->query('q').'%')
                    ->orWhere('subject_type', 'like', '%'.$request->query('q').'%'));
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('activity.index', [
            'data' => $data,
            'logs' => DB::table('activity_log')->select('log_name')->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name'),
            'pengguna' => \App\Models\User::query()->orderBy('name')->get(),
            'filter' => [
                'dari' => $dari->toDateString(),
                'sampai' => $sampai->toDateString(),
                'log' => $request->query('log'),
                'causer' => $request->query('causer'),
                'q' => (string) $request->query('q', ''),
            ],
        ]);
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
}
