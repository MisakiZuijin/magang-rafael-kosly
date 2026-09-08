<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LogAktivitasService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MitraPenggunaController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index(Request $request)
    {
        $mitraId = Auth::id();

        $penghunis = User::where('role', 'penghuni')
            ->where('created_by', $mitraId)
            ->with(['penghuniKamar' => function ($query) use ($mitraId) {
                $query->whereHas('kamar.kos', function ($k) use ($mitraId) {
                    $k->where('mitra_id', $mitraId);
                })->with(['kamar.kos', 'pembayaran'])->latest();
            }])
            ->latest()
            ->get();

        return view('mitra.penghuni.index', compact('penghunis'));
    }

    public function create()
    {
        return view('mitra.penghuni.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'no_hp' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $validated['role'] = 'penghuni';
        $validated['is_active'] = true;
        $validated['created_by'] = Auth::id();

        $user = $this->userService->createUser($validated);
        $mitra = Auth::user();

        $this->logAktivitasService->log('tambah_penghuni_mitra_pro', "Mitra Pro {$mitra->nama} mendaftarkan akun penghuni baru: {$user->nama} ({$user->email})");

        return redirect()->route('mitra.penghuni.index')->with('success', "Akun penghuni '{$user->nama}' berhasil dibuat.");
    }

    public function edit(string|int $id)
    {
        $mitraId = Auth::id();
        $user = User::where('role', 'penghuni')
            ->where('created_by', $mitraId)
            ->where(function ($q) use ($id) {
                $q->where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

        return view('mitra.penghuni.edit', compact('user'));
    }

    public function update(Request $request, string|int $id)
    {
        $mitraId = Auth::id();
        $user = User::where('role', 'penghuni')
            ->where('created_by', $mitraId)
            ->where(function ($q) use ($id) {
                $q->where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $this->userService->updateUser($user->id, $validated);
        $this->logAktivitasService->log('update_penghuni_mitra_pro', "Mitra Pro " . Auth::user()->nama . " memperbarui data akun penghuni: {$validated['nama']}");

        return redirect()->route('mitra.penghuni.index')->with('success', "Data akun penghuni '{$validated['nama']}' berhasil diperbarui.");
    }

    public function destroy(string|int $id)
    {
        $mitraId = Auth::id();
        $user = User::where('role', 'penghuni')
            ->where('created_by', $mitraId)
            ->where(function ($q) use ($id) {
                $q->where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0);
            })
            ->firstOrFail();

        $nama = $user->nama;
        $this->userService->deleteUser($user->id);
        $this->logAktivitasService->log('hapus_penghuni_mitra_pro', "Mitra Pro " . Auth::user()->nama . " menghapus akun penghuni: {$nama}");

        return redirect()->route('mitra.penghuni.index')->with('success', "Akun penghuni '{$nama}' berhasil dihapus permanen.");
    }

    public function toggleActive($id)
    {
        $mitraId = Auth::id();
        $user = User::where('role', 'penghuni')
            ->where('created_by', $mitraId)
            ->findOrFail($id);

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->logAktivitasService->log('toggle_status_penghuni', "Mitra Pro " . Auth::user()->nama . " {$status} akun penghuni {$user->nama}");

        return back()->with('success', "Status akun '{$user->nama}' berhasil {$status}.");
    }
}
