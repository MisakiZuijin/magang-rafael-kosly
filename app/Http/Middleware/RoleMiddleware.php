<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi pengelola untuk mengaktifkan kembali.',
            ]);
        }

        // Strictly verify user role matches the required role(s) for the current route
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // Redirect user to their appropriate role dashboard if accessing unauthorized route
        return match ($user->role) {
            'super_admin' => redirect()->route('superadmin.dashboard')->with('error', 'Akses ditolak. Anda telah dialihkan ke Dashboard Super Admin.'),
            'admin' => redirect()->route('admin.dashboard')->with('error', 'Akses ditolak. Anda telah dialihkan ke Dashboard Admin.'),
            'mitra' => redirect()->route('mitra.dashboard')->with('error', 'Akses ditolak. Anda telah dialihkan ke Dashboard Mitra.'),
            'penghuni' => redirect()->route('penghuni.dashboard')->with('error', 'Akses ditolak. Anda telah dialihkan ke Dashboard Penghuni.'),
            default => abort(403, 'Akses ditolak. Peran ' . $user->role . ' tidak memiliki izin mengakses halaman ini.'),
        };
    }
}
