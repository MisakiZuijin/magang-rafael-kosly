<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LogAktivitasService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPenggunaController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $mitras = $this->userService->getByRole('mitra');
        $penghunis = $this->userService->getByRole('penghuni');

        $view = request()->is('superadmin*') ? 'superadmin.pengguna.index' : 'admin.pengguna.index';
        return view($view, compact('mitras', 'penghunis'));
    }

    public function create()
    {
        $view = request()->is('superadmin*') ? 'superadmin.pengguna.create' : 'admin.pengguna.create';
        return view($view);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $allowedRoles = $currentUser->role === 'super_admin' ? 'in:admin,mitra,penghuni' : 'in:mitra,penghuni';

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'no_hp' => 'nullable|string|max:20',
            'role' => 'required|' . $allowedRoles,
        ]);

        $user = $this->userService->createUser($validated);
        $this->logAktivitasService->log('tambah_pengguna', "Menambahkan akun pengguna baru: {$user->nama} ({$user->role})");

        $prefix = request()->is('superadmin*') ? 'superadmin.' : 'admin.';
        return redirect()->route($prefix . 'pengguna.index')->with('success', 'Pengguna berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $user = $this->userService->getUserById($id);
        $view = request()->is('superadmin*') ? 'superadmin.pengguna.edit' : 'admin.pengguna.edit';
        return view($view, compact('user'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
            'no_hp' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $this->userService->updateUser($id, $validated);
        $this->logAktivitasService->log('update_pengguna', "Memperbarui data akun pengguna: {$validated['nama']}");

        $prefix = request()->is('superadmin*') ? 'superadmin.' : 'admin.';
        return redirect()->route($prefix . 'pengguna.index')->with('success', 'Pengguna berhasil diupdate.');
    }

    public function toggleActive(int $id)
    {
        $user = User::find($id);
        $this->userService->toggleActive($id);
        $this->logAktivitasService->log('toggle_pengguna', "Mengubah status aktif pengguna: {$user->nama}");

        return redirect()->back()->with('success', 'Status pengguna berhasil diubah.');
    }
}
