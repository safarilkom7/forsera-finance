@extends('layouts.app')

@section('title', 'Pengguna')
@section('subtitle', 'Owner, Admin Keuangan, dan Staf Unit')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="muted">Owner punya akses penuh; Staf Unit hanya melihat unit yang ditugaskan.</p>
        <div class="ml-auto flex shrink-0 items-center gap-2">
            @if ($filter !== [])
                <a href="{{ route('users.index') }}" class="btn-ghost btn-sm whitespace-nowrap">
                    <x-icon name="refresh" class="h-3.5 w-3.5" /> Reset filter
                </a>
            @endif
            <a href="{{ route('users.create') }}" class="btn-primary whitespace-nowrap"><x-icon name="plus" class="h-4 w-4" /> Pengguna baru</a>
        </div>
    </div>

    <form method="GET" action="{{ route('users.index') }}" id="filter-pengguna" class="hidden" data-filter-form>
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
                    <th>Unit</th>
                    <th>Status</th>
                    <th class="text-right">Login terakhir</th>
                    <th></th>
                </tr>
                <tr class="filter-baris">
                    <x-filter-teks name="f_nama" :value="$filter['f_nama'] ?? ''" placeholder="Cari nama" form="filter-pengguna" ringkas />
                    <x-filter-teks name="f_email" :value="$filter['f_email'] ?? ''" placeholder="Cari email" form="filter-pengguna" ringkas />
                    <x-filter-pilih name="f_peran" :value="$filter['f_peran'] ?? ''" :options="$peran" placeholder="Semua peran" form="filter-pengguna" ringkas />
                    <x-filter-pilih name="f_unit" :value="$filter['f_unit'] ?? ''"
                                    :options="$units->pluck('nama', 'id')->all() + ['tanpa' => 'Tanpa unit']"
                                    placeholder="Semua unit" form="filter-pengguna" ringkas />
                    <x-filter-pilih name="f_status" :value="$filter['f_status'] ?? ''"
                                    :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']"
                                    placeholder="Semua status" form="filter-pengguna" ringkas />
                    <x-filter-pilih name="f_login" :value="$filter['f_login'] ?? ''"
                                    :options="['ada' => 'Pernah login', 'kosong' => 'Belum pernah login']"
                                    placeholder="Semua login" form="filter-pengguna" ringkas />
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
                        <td class="text-xs">{{ $user->unitUsaha->pluck('nama')->join(', ') ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $user->aktif ? 'badge-green' : 'badge-gray' }}">{{ $user->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                            @if ($user->must_change_password)
                                <span class="badge badge-yellow mt-1">Ganti password</span>
                            @endif
                        </td>
                        <td class="text-right text-xs">{{ $user->last_login_at ? format_tanggal($user->last_login_at).' '.$user->last_login_at->format('H:i') : '—' }}</td>
                        <td class="text-right"><a href="{{ route('users.edit', $user) }}" class="btn-ghost btn-sm">Ubah</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">
                            @if ($filter !== [])
                                Tidak ada pengguna yang cocok. <a href="{{ route('users.index') }}" class="font-semibold text-emerald-700 underline dark:text-emerald-400">Reset filter</a>.
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
