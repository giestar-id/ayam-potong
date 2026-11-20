<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->employee && ! $user->employee->is_active) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $user->tokens()->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Akun pegawai Anda sedang dinonaktifkan. Silakan hubungi pemilik gerai.',
                ], 401);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun pegawai Anda sedang dinonaktifkan. Silakan hubungi pemilik gerai.',
            ]);
        }

        return $next($request);
    }
}
