<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profil.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'password_lama' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.unique' => 'Email sudah dipakai pengguna lain.',
            'password.confirmed' => 'Ulangi password baru dengan benar.',
        ]);

        if (! empty($data['password']) && ! Hash::check((string) ($data['password_lama'] ?? ''), $user->password)) {
            return back()->withErrors(['password_lama' => 'Password saat ini tidak sesuai.'])->withInput();
        }

        $perubahan = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $perubahan['password'] = $data['password'];
            $perubahan['must_change_password'] = false;
        }

        $user->update($perubahan);

        activity('pengguna')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['perubahan' => array_keys($perubahan)])
            ->log('Memperbarui profil');

        return back()->with('sukses', 'Profil berhasil diperbarui.');
    }
}
