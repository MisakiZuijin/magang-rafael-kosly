<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;

class AdminPenggunaController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index()
    {
        $mitras = $this->userService->getActiveByRole('mitra');
        $penghunis = $this->userService->getActiveByRole('penghuni');

        return view('admin.pengguna.index', compact('mitras', 'penghunis'));
    }

    public function create()
    {
        return view('admin.pengguna.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'no_hp' => 'nullable|string|max:20',
            'role' => 'required|in:mitra,penghuni',
        ]);

        $this->userService->createUser($validated);

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil dibuat.');
    }

    public function edit(int $id)
    {
        $user = $this->userService->getUserById($id);
        return view('admin.pengguna.edit', compact('user'));
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

        return redirect()->route('admin.pengguna.index')->with('success', 'Pengguna berhasil diupdate.');
    }

    public function toggleActive(int $id)
    {
        $this->userService->toggleActive($id);
        return redirect()->back()->with('success', 'Status pengguna berhasil diubah.');
    }
}
