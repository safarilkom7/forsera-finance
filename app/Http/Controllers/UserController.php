<?php

namespace App\Http\Controllers;

use App\Models\UnitUsaha;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->with(['roles', 'unitUsaha'])
            ->when(trim((string) $request->query('q')) !== '', function ($q) use ($request) {
                $q->where(fn ($r) => $r
                    ->where('name', 'like', '%'.$request->query('q').'%')
                    ->orWhere('email', 'like', '%'.$request->query('q').'%'));
            })
            ->orderBy('name');

        return view('users.index', [
            'data' => $query->paginate(20)->withQueryString(),
            'q' => (string) $request->query('q', ''),
        ]);
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => null,
            'units' => UnitUsaha::query()->orderBy('nama')->get(),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'aktif' => $request->boolean('aktif'),
            'must_change_password' => true,
        ]);

        $user->syncRoles([$data['role']]);
        $this->syncUnit($user, $data, $data['role']);

        activity('pengguna')->performedOn($user)->causedBy($request->user())
            ->withProperties(['role' => $data['role'], 'units' => $data['units'] ?? []])
            ->log('Membuat pengguna');

        return redirect()->route('users.index')->with('sukses', 'Pengguna berhasil dibuat. Password awal harus diganti saat login pertama.');
    }

    public function edit(User $user): View
    {
        $user->load(['roles', 'unitUsaha']);

        return view('users.form', [
            'user' => $user,
            'units' => UnitUsaha::query()->orderBy('nama')->get(),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validasi($request, $user);

        if ($user->id === $request->user()->id && $data['role'] !== User::ROLE_OWNER) {
            throw ValidationException::withMessages(['role' => 'Anda tidak dapat menghapus peran Owner dari akun sendiri.']);
        }

        $this->pastikanOwnerTerakhir($user, $data['role'], $request->boolean('aktif'));

        if ($user->id === $request->user()->id && ! $request->boolean('aktif')) {
            throw ValidationException::withMessages(['aktif' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $perubahan = [
            'name' => $data['name'],
            'email' => $data['email'],
            'aktif' => $request->boolean('aktif'),
        ];

        if (! empty($data['password'])) {
            $perubahan['password'] = $data['password'];
            $perubahan['must_change_password'] = true;
            $perubahan['remember_token'] = null;
        }

        $user->update($perubahan);
        $user->syncRoles([$data['role']]);
        $this->syncUnit($user, $data, $data['role']);

        activity('pengguna')->performedOn($user)->causedBy($request->user())
            ->withProperties(['role' => $data['role'], 'units' => $data['units'] ?? []])
            ->log('Memperbarui pengguna');

        return redirect()->route('users.index')->with('sukses', 'Pengguna berhasil diperbarui.');
    }

    private function syncUnit(User $user, array $data, string $role): void
    {
        $user->unitUsaha()->sync($role === User::ROLE_STAF ? ($data['units'] ?? []) : []);
    }

    private function pastikanOwnerTerakhir(User $user, string $roleBaru, bool $aktif): void
    {
        if (! $user->hasRole(User::ROLE_OWNER)) {
            return;
        }

        if ($roleBaru === User::ROLE_OWNER && $aktif) {
            return;
        }

        $ownerAktif = User::role(User::ROLE_OWNER)->where('aktif', true)->count();

        if ($ownerAktif <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Minimal harus ada satu Owner aktif. Tambahkan Owner lain sebelum mengubah akun ini.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in([User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_STAF])],
            'units' => ['nullable', 'array'],
            'units.*' => ['integer', 'exists:unit_usaha,id'],
            'aktif' => ['boolean'],
            'password' => [$user === null ? 'required' : 'nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah dipakai pengguna lain.',
            'role.required' => 'Peran wajib dipilih.',
            'password.required' => 'Password awal wajib diisi.',
            'password.confirmed' => 'Ulangi password dengan benar.',
        ]);

        if ($data['role'] === User::ROLE_STAF && empty($data['units'])) {
            throw ValidationException::withMessages(['units' => 'Pilih minimal satu unit untuk Staf Unit.']);
        }

        return $data;
    }
}
