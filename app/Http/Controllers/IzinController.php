<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\FilterTabel;
use App\Support\PetaIzin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class IzinController extends Controller
{
    public function index(Request $request): View
    {
        $peta = [
            'f_nama' => ['kolom' => 'name'],
            'f_email' => ['kolom' => 'email'],
            'f_peran' => [
                'kolom' => 'name',
                'jenis' => 'pilih_relasi',
                'relasi' => 'roles',
                'dalam' => [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_STAF],
            ],
            'f_status' => ['kolom' => 'aktif', 'jenis' => 'aktif'],
        ];

        $filter = FilterTabel::nilai($request, $peta);

        $query = User::query()->with('roles:id,name')->withCount('permissions');
        FilterTabel::terapkan($query, $filter, $peta);

        return view('izin.index', [
            'data' => $query->orderBy('name')->paginate(20)->withQueryString(),
            'filter' => $filter,
            'peran' => [
                User::ROLE_OWNER => 'Owner',
                User::ROLE_ADMIN => 'Admin Keuangan',
                User::ROLE_STAF => 'Staf Unit',
            ],
        ]);
    }

    public function edit(User $user): View
    {
        $user->load(['roles.permissions:id,name', 'permissions:id,name']);

        // Izin yang datang dari peran tidak bisa dilepas di sini — itu bagian
        // definisi peran di seeder. Matriks hanya mengatur izin khusus.
        $dariPeran = $user->roles->flatMap->permissions->pluck('name')->unique();
        $khusus = $user->permissions->pluck('name');

        return view('izin.matriks', [
            'user' => $user,
            'grup' => PetaIzin::GRUP,
            'dariPeran' => $dariPeran,
            'khusus' => $khusus,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validasi($request);

        // Hanya izin langsung milik pengguna yang diganti; izin bawaan peran tetap utuh.
        $user->syncPermissions($data['izin'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('izin')->performedOn($user)->causedBy($request->user())
            ->withProperties(['izin_khusus' => $data['izin'] ?? []])
            ->log('Mengatur hak akses khusus');

        return redirect()->route('izin.index')->with('sukses', "Hak akses khusus untuk {$user->name} berhasil disimpan.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        // izin.kelola tidak pernah diberikan lewat matriks agar menu Hak Akses
        // tidak bisa memperbesar haknya sendiri; hanya Owner (via seeder) yang memilikinya.
        $daftarIzin = Permission::query()->where('name', '!=', 'izin.kelola')->pluck('name')->all();

        return $request->validate([
            'izin' => ['nullable', 'array'],
            'izin.*' => ['string', Rule::in($daftarIzin)],
        ], [
            'izin.*.in' => 'Ada izin yang tidak dikenal.',
        ]);
    }
}
