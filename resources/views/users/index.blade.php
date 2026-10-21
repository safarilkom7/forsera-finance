@extends('layouts.app')

@section('title', 'Pengguna')
@section('subtitle', 'Owner, Admin Keuangan, dan Staf Unit')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('users.index') }}" class="flex gap-2">
            <input type="search" name="q" value="{{ $q }}" class="input w-56" placeholder="Cari nama atau email">
            <button class="btn-primary">Cari</button>
        </form>
        <a href="{{ route('users.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Pengguna baru</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Unit</th><th>Status</th><th class="text-right">Login terakhir</th><th></th></tr></thead>
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
                    <tr><td colspan="7" class="py-8 text-center text-slate-500">Tidak ada pengguna.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $data->links() }}</div>
@endsection
