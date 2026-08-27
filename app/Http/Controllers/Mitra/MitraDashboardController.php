<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\KamarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

    public function showKamar(int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');
        $kamar = \App\Models\Kamar::with(['kos.mitra', 'penghuniKamar.penghuni'])
            ->whereIn('kos_id', $mitraKosIds)
            ->findOrFail($id);

        return view('mitra.show', compact('kamar'));
    }
}
