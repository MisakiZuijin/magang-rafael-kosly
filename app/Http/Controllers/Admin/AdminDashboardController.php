<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\KamarService;
use App\Services\PenghuniKamarService;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected KamarService $kamarService,
        protected PenghuniKamarService $penghuniKamarService
    ) {}

    public function index()
    {
        $this->penghuniKamarService->periksaSemuaNotifikasiSewa();
        $data = $this->dashboardService->getAdminData();
        return view('admin.dashboard', compact('data'));
    }
}
