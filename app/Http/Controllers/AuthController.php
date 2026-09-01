<?php

namespace App\Http\Controllers;

use App\Services\LogAktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected LogAktivitasService $logService
    ) {}

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if ($user && !$user->is_active) {
            return back()->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi pengelola untuk mengaktifkan kembali.',
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            if ($remember) {
                \Illuminate\Support\Facades\Cookie::queue('remembered_email', $credentials['email'], 60 * 24 * 365);
            } else {
                \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget('remembered_email'));
                \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget(Auth::getRecallerName()));
                \App\Models\User::where('id', Auth::id())->update(['remember_token' => null]);
            }

            $this->logService->log('login', 'User melakukan login', Auth::id());

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        if ($userId) {
            $this->logService->log('logout', 'User melakukan logout', $userId);
            \App\Models\User::where('id', $userId)->update(['remember_token' => null]);
        }

        \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget(Auth::getRecallerName()));

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
