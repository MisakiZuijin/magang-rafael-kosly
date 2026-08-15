<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $data = [];

        switch ($user->role) {
            case 'penghuni':
                $data = $this->dashboardService->getPenghuniData($user->id);
                return view('penghuni.dashboard', compact('data'));

            case 'mitra':
                $data = $this->dashboardService->getMitraData($user->id);
                return view('mitra.dashboard', compact('data'));

            case 'admin':
                $data = $this->dashboardService->getAdminData();
                return view('admin.dashboard', compact('data'));

            case 'super_admin':
                $data = $this->dashboardService->getSuperAdminData();
                return view('superadmin.dashboard', compact('data'));

            default:
                abort(403);
        }
    }
}
