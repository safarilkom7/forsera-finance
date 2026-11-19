@extends('layouts.app')

@section('title', 'Hak Akses')
@section('subtitle', 'Izin khusus per pengguna')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="muted">Izin khusus hanya tambahan di atas peran — menghapusnya mengembalikan akses ke default peran.</p>
        <div class="ml-auto flex shrink-0 items-center gap-2">
            @if ($filter !== [])
                <a href="{{ route('izin.index') }}" class="btn-ghost btn-sm whitespace-nowrap">
                    <x-icon name="refresh" class="h-3.5 w-3.5" /> Reset filter
                </a>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('izin.index') }}" id="filter-izin" class="hidden" data-filter-form>
        <button type="submit" tabindex="-1" aria-hidden="true">Terapkan</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Izin khusus</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                <tr class="filter-baris">
                    <x-filter-teks name="f_nama" :value="$filter['f_nama'] ?? ''" placeholder="Cari nama" form="filter-izin" ringkas />
                    <x-filter-teks name="f_email" :value="$filter['f_email'] ?? ''" placeholder="Cari email" form="filter-izin" ringkas />
                    <x-filter-pilih name="f_peran" :value="$filter['f_peran'] ?? ''" :options="$peran" placeholder="Semua peran" form="filter-izin" ringkas />
                    <x-filter-pilih name="f_status" :value="$filter['f_status'] ?? ''"
                                    :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']"
                                    placeholder="Semua status" form="filter-izin" ringkas />
                    <x-filter-kosong />
                </tr>
                </thead>
                <tbody>
                @forelse ($data as $user)
                    <tr>
                        <td class="font-medium text-slate-900 dark:text-slate-100">{{ $user->name }}</td>
                        <td class="text-xs">{{ $user->email }}</td>
                        <td>
                            @foreach ($user->roles as $role)
                                <span class="badge {{ $role->name === 'owner' ? 'badge-blue' : ($role->name === 'admin_keuangan' ? 'badge-green' : 'badge-gray') }}">{{ str_replace('_', ' ', $role->name) }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if ($user->permissions_count > 0)
                                <span class="badge badge-yellow">{{ $user->permissions_count }} izin khusus</span>
                            @else
                                <span class="text-xs text-slate-500 dark:text-slate-400">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $user->aktif ? 'badge-green' : 'badge-gray' }}">{{ $user->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="text-right"><a href="{{ route('izin.edit', $user) }}" class="btn-ghost btn-sm">Atur</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">
                            @if ($filter !== [])
                                Tidak ada pengguna yang cocok. <a href="{{ route('izin.index') }}" class="font-semibold text-emerald-700 underline dark:text-emerald-400">Reset filter</a>.
                            @else
                                Belum ada pengguna.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
@endsection
