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
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $admins = $currentUser->role === 'super_admin' ? $this->userService->getByRole('admin') : collect();
        $mitras = $this->userService->getByRole('mitra');
        $penghunis = $this->userService->getByRole('penghuni');

        $view = request()->is('superadmin*') ? 'superadmin.pengguna.index' : 'admin.pengguna.index';
        return view($view, compact('admins', 'mitras', 'penghunis'));
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
            'password' => 'required|min:6|confirmed',
            'no_hp' => 'nullable|string|max:20',
            'role' => 'required|' . $allowedRoles,
        ]);

        $user = $this->userService->createUser($validated);
        $this->logAktivitasService->log('tambah_pengguna', "Menambahkan akun pengguna baru: {$user->nama} ({$user->role})");

        $prefix = request()->is('superadmin*') ? 'superadmin.' : 'admin.';
        return redirect()->route($prefix . 'pengguna.index')->with('success', 'Pengguna berhasil dibuat.');
    }

    public function edit(string|int $id)
    {
        $user = User::where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0)->firstOrFail();
        
        // Admin biasa tidak bisa mengedit akun Admin
        if ($user->role === 'admin' && Auth::user()->role !== 'super_admin') {
            return redirect()->back()->with('error', 'Akses ditolak. Pengelolaan akun Admin hanya dapat dilakukan oleh Super Admin.');
        }

        $view = request()->is('superadmin*') ? 'superadmin.pengguna.edit' : 'admin.pengguna.edit';
        return view($view, compact('user'));
    }

    public function update(Request $request, string|int $id)
    {
        $user = User::where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0)->firstOrFail();

        if ($user->role === 'admin' && Auth::user()->role !== 'super_admin') {
            return redirect()->back()->with('error', 'Akses ditolak. Pengelolaan akun Admin hanya dapat dilakukan oleh Super Admin.');
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'no_hp' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $this->userService->updateUser($user->id, $validated);
        $this->logAktivitasService->log('update_pengguna', "Memperbarui data akun pengguna: {$validated['nama']}");

        $prefix = request()->is('superadmin*') ? 'superadmin.' : 'admin.';
        return redirect()->route($prefix . 'pengguna.index')->with('success', 'Pengguna berhasil diupdate.');
    }

    public function toggleActive(string|int $id)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $user = User::where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0)->firstOrFail();

        // Admin biasa tidak diizinkan menonaktifkan akun Super Admin atau sesama Admin
        if ($currentUser->role !== 'super_admin' && in_array($user->role, ['admin', 'super_admin'])) {
            return redirect()->back()->with('error', 'Akses ditolak. Pengelolaan status akun Admin hanya dapat dilakukan oleh Super Admin.');
        }

        $wasActive = $user->is_active;

        $this->userService->toggleActive($user->id);

        $actorRole = $currentUser->role === 'super_admin' ? 'Super Admin' : 'Admin';

        if ($wasActive) {
            $this->logAktivitasService->log('toggle_pengguna', "{$actorRole} menonaktifkan akun {$user->role}: {$user->nama} (Akses login ditutup).");
            return redirect()->back()->with('success', "Akun pengguna {$user->nama} berhasil dinonaktifkan. Pengguna ini tidak dapat melakukan login ke sistem.");
        } else {
            $this->logAktivitasService->log('toggle_pengguna', "{$actorRole} mengaktifkan kembali akun {$user->role}: {$user->nama}");
            return redirect()->back()->with('success', "Akun pengguna {$user->nama} berhasil diaktifkan kembali.");
        }
    }

    public function destroy(string|int $id)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser->role !== 'super_admin') {
            return redirect()->back()->with('error', 'Akses ditolak. Aksi hapus pengguna hanya dapat dilakukan oleh Super Admin.');
        }

        $user = User::where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0)->firstOrFail();
        $nama = $user->nama;
        $role = $user->role;

        $this->userService->deleteUser($user->id);
        $this->logAktivitasService->log('hapus_pengguna', "Super Admin menghapus permanen akun {$role}: {$nama} beserta seluruh log aktivitas terkait.");

        $prefix = request()->is('superadmin*') ? 'superadmin.' : 'admin.';
        return redirect()->route($prefix . 'pengguna.index')->with('success', "Akun {$role} {$nama} dan seluruh data log aktivitasnya berhasil dihapus permanen dari sistem.");
    }
}
