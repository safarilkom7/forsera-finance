<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function formLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $kunci = Str::lower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $detik = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.$detik.' detik.',
            ]);
        }

        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null || ! $user->aktif || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($kunci, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        Auth::login($user, (bool) ($data['remember'] ?? false));
        $request->session()->regenerate();
        RateLimiter::clear($kunci);

        $user->forceFill(['last_login_at' => now()])->save();

        activity('auth')
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent()])
            ->log('Login');

        if ($user->must_change_password) {
            return redirect()->route('password.form');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            activity('auth')
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('Logout');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('sukses', 'Anda telah keluar dari aplikasi.');
    }

    public function formUbahPassword(): View
    {
        return view('auth.ubah-password');
    }

    public function ubahPassword(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'password_lama' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'password_lama.required' => 'Password saat ini wajib diisi.',
            'password.confirmed' => 'Ulangi password baru dengan benar.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! Hash::check((string) $request->input('password_lama'), (string) $request->user()->password)) {
                $validator->errors()->add('password_lama', 'Password saat ini tidak sesuai.');
            }
        });

        $validator->validate();

        $user = $request->user();
        $user->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        activity('auth')
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Mengganti password');

        return redirect()->route('dashboard')->with('sukses', 'Password berhasil diperbarui.');
    }
}
