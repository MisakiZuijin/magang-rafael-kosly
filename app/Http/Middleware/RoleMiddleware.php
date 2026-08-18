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

        // Enforce strict role boundary check for ALL users including super_admin
        if (!in_array($user->role, $roles)) {
            abort(403, 'Akses ditolak. Peran ' . $user->role . ' tidak memiliki izin mengakses halaman ini.');
        }

        return $next($request);
    }
}
