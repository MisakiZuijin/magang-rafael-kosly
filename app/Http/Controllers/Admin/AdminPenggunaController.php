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
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser->role !== 'super_admin') {
            return redirect()->back()->with('error', 'Aksi penonaktifan pengguna hanya dapat dilakukan oleh Super Admin.');
        }

        $user = User::findOrFail($id);
        $wasActive = $user->is_active;

        $this->userService->toggleActive($id);

        if ($wasActive) {
            $this->logAktivitasService->log('toggle_pengguna', "Super Admin menonaktifkan akun {$user->role}: {$user->nama} & menghapus seluruh data log aktivitasnya.");
            return redirect()->back()->with('success', "Akun pengguna {$user->nama} berhasil dinonaktifkan. Seluruh data log aktivitas pengguna ini di database telah dihapus.");
        } else {
            $this->logAktivitasService->log('toggle_pengguna', "Super Admin mengaktifkan kembali akun {$user->role}: {$user->nama}");
            return redirect()->back()->with('success', "Akun pengguna {$user->nama} berhasil diaktifkan kembali.");
        }
    }

    public function destroy(int $id)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser->role !== 'super_admin') {
            return redirect()->back()->with('error', 'Aksi hapus pengguna hanya dapat dilakukan oleh Super Admin.');
        }

        $user = User::findOrFail($id);
        $nama = $user->nama;
        $role = $user->role;

        $this->userService->deleteUser($id);
        $this->logAktivitasService->log('hapus_pengguna', "Super Admin menghapus permanen akun {$role}: {$nama}");

        $prefix = request()->is('superadmin*') ? 'superadmin.' : 'admin.';
        return redirect()->route($prefix . 'pengguna.index')->with('success', "Akun {$role} {$nama} berhasil dihapus permanen dari sistem.");
    }
}
