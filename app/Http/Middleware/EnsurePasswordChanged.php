<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->must_change_password && ! $request->routeIs('password.*') && ! $request->routeIs('logout')) {
            return redirect()->route('password.form')
                ->with('gagal', 'Demi keamanan, ganti password sementara Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
