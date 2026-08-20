<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Kantor;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\LogAktivitasService;
use App\Services\UserService;
use Illuminate\Http\Request;

class SuperAdminDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected UserService $userService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function dashboard()
    {
        $data = $this->dashboardService->getSuperAdminData();
        return view('superadmin.dashboard', compact('data'));
    }

    // ==========================================
    // KELOLA ADMIN
    // ==========================================

    public function adminIndex()
    {
        $admins = $this->userService->getByRole('admin');
        return view('superadmin.admin.index', compact('admins'));
    }

    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $validated['role'] = 'admin';
        $user = $this->userService->createUser($validated);
        $this->logAktivitasService->log('tambah_admin', "Super Admin membuat akun Admin baru: {$user->nama}");

        return redirect()->route('superadmin.admin.index')->with('success', 'Admin berhasil dibuat.');
    }

    public function adminUpdate(Request $request, int $id)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $this->userService->updateUser($id, array_filter($validated));
        $this->logAktivitasService->log('update_admin', "Super Admin memperbarui data Admin ID: {$id}");

        return redirect()->route('superadmin.admin.index')->with('success', 'Data admin berhasil diupdate.');
    }

    public function adminToggle(int $id)
    {
        $user = User::find($id);
        $this->userService->toggleActive($id);
        $this->logAktivitasService->log('toggle_admin', "Super Admin mengubah status aktif Admin: " . ($user->nama ?? $id));

        return redirect()->back()->with('success', 'Status admin berhasil diubah.');
    }

    public function adminDestroy(int $id)
    {
        $user = User::where('role', 'admin')->findOrFail($id);
        $nama = $user->nama;
        $user->delete();
        $this->logAktivitasService->log('hapus_admin', "Super Admin menghapus akun Admin: {$nama}");

        return redirect()->route('superadmin.admin.index')->with('success', 'Akun admin berhasil dihapus.');
    }

    // ==========================================
    // KELOLA LOKASI KANTOR
    // ==========================================

    public function kantorIndex()
    {
        $kantors = Kantor::latest()->get();
        return view('superadmin.kantor.index', compact('kantors'));
    }

    public function kantorStore(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'alamat' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'no_telp' => 'nullable|string|max:30',
        ]);

        $kantor = Kantor::create($validated);
        $this->logAktivitasService->log('tambah_kantor', "Menambahkan lokasi kantor baru: {$kantor->nama}");

        return redirect()->route('superadmin.kantor.index')->with('success', 'Lokasi kantor baru berhasil ditambahkan.');
    }

    public function kantorUpdate(Request $request, int $id)
    {
        $kantor = Kantor::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'alamat' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'no_telp' => 'nullable|string|max:30',
        ]);

        $kantor->update($validated);
        $this->logAktivitasService->log('update_kantor', "Memperbarui lokasi kantor: {$kantor->nama}");

        return redirect()->route('superadmin.kantor.index')->with('success', 'Data lokasi kantor berhasil diupdate.');
    }

    public function kantorToggle(int $id)
    {
        $kantor = Kantor::findOrFail($id);
        $kantor->update(['is_active' => !$kantor->is_active]);
        $this->logAktivitasService->log('toggle_kantor', "Mengubah status aktif kantor: {$kantor->nama}");

        return redirect()->back()->with('success', 'Status kantor berhasil diubah.');
    }

    public function kantorDestroy(int $id)
    {
        $kantor = Kantor::findOrFail($id);
        $nama = $kantor->nama;
        $kantor->delete();
        $this->logAktivitasService->log('hapus_kantor', "Menghapus lokasi kantor: {$nama}");

        return redirect()->route('superadmin.kantor.index')->with('success', 'Data lokasi kantor berhasil dihapus.');
    }
}
