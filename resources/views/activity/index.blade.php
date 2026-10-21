@extends('layouts.app')

@section('title', 'Log Aktivitas')
@section('subtitle', 'Jejak audit semua perubahan dan akses')

@section('content')
    <form method="GET" action="{{ route('activity.index') }}" class="card mb-5 card-pad">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="dari" class="label">Dari</label>
                <x-tanggal id="dari" name="dari" value="{{ $filter['dari'] }}" class="input" />
            </div>
            <div>
                <label for="sampai" class="label">Sampai</label>
                <x-tanggal id="sampai" name="sampai" value="{{ $filter['sampai'] }}" class="input" />
            </div>
            <div>
                <label for="log" class="label">Kategori log</label>
                <x-pilihan id="log" name="log" class="input">
                    <option value="">Semua</option>
                    @foreach ($logs as $log)
                        <option value="{{ $log }}" @selected($filter['log'] === $log)>{{ $log }}</option>
                    @endforeach
                </x-pilihan>
            </div>
            <div>
                <label for="causer" class="label">Pengguna</label>
                <x-pilihan id="causer" name="causer" class="input">
                    <option value="">Semua</option>
                    @foreach ($pengguna as $user)
                        <option value="{{ $user->id }}" @selected((string) $filter['causer'] === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </x-pilihan>
            </div>
            <div>
                <label for="q" class="label">Cari deskripsi</label>
                <input type="search" id="q" name="q" value="{{ $filter['q'] }}" class="input">
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button class="btn-primary">Terapkan</button>
            <a href="{{ route('activity.index') }}" class="btn-secondary">Reset</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Kategori</th><th>Aksi</th><th>Objek</th><th>Detail</th><th>IP</th></tr></thead>
                <tbody>
                @forelse ($data as $log)
                    <tr>
                        <td class="whitespace-nowrap text-xs">{{ format_tanggal($log->created_at).' '.$log->created_at?->format('H:i:s') }}</td>
                        <td class="text-xs">{{ $log->causer?->name ?? 'Sistem' }}</td>
                        <td class="text-xs">{{ $log->log_name ?? '—' }}</td>
                        <td class="text-sm font-medium">{{ $log->description }}</td>
                        <td class="text-xs text-slate-500">
                            @if ($log->subject_type)
                                {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                            @endif
                        </td>
                        <td class="max-w-xs">
                            @if ($log->properties && $log->properties->isNotEmpty())
                                <details>
                                    <summary class="cursor-pointer text-xs font-medium text-emerald-700 dark:text-emerald-400">Lihat data</summary>
                                    <pre class="mt-1 max-h-40 overflow-auto whitespace-pre-wrap rounded bg-slate-50 p-2 text-[10px] text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ json_encode($log->properties->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @else
                                <span class="text-xs text-slate-500 dark:text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-[11px] text-slate-500 dark:text-slate-400">{{ $log->properties['ip'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-slate-500">Tidak ada aktivitas pada filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
@endsection
