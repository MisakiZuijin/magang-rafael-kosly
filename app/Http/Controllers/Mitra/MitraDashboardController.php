<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\KamarService;
use Illuminate\Support\Facades\Auth;

class MitraDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected KamarService $kamarService
    ) {}

    public function index()
    {
        $data = $this->dashboardService->getMitraData(Auth::id());
        return view('mitra.dashboard', compact('data'));
    }

    public function kamar()
    {
        $user = Auth::user();
        $kosList = $user->kos;

        $kamarData = collect();
        foreach ($kosList as $kos) {
            $kamars = $this->kamarService->getByKosWithPenghuni($kos->id);
            $kamarData = $kamarData->merge($kamars);
        }

        return view('mitra.kamar', compact('kamarData', 'kosList'));
    }
}
