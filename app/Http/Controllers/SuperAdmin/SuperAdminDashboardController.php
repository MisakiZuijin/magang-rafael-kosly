<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\UserService;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected UserService $userService
    ) {}

    public function dashboard()
    {
        $data = $this->dashboardService->getSuperAdminData();
        return view('superadmin.dashboard', compact('data'));
    }

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
        $this->userService->createUser($validated);

        return redirect()->route('superadmin.admin.index')->with('success', 'Admin berhasil dibuat.');
    }

    public function adminToggle(int $id)
    {
        $this->userService->toggleActive($id);
        return redirect()->back()->with('success', 'Status admin diubah.');
    }
}
