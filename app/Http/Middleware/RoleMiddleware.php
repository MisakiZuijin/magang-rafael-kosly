<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi pengelola untuk mengaktifkan kembali.',
            ]);
        }

        // Allow super_admin or users with matching roles
        if ($user->role === 'super_admin' || in_array($user->role, $roles)) {
            return $next($request);
        }

        abort(403, 'Akses ditolak. Peran ' . $user->role . ' tidak memiliki izin mengakses halaman ini.');

        return $next($request);
    }
}
