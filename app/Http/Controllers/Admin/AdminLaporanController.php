<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\KamarService;
use App\Services\KosService;
use App\Services\PembayaranService;
use App\Services\PenghuniKamarService;
use Illuminate\Http\Request;

class AdminLaporanController extends Controller
{
    public function __construct(
        protected PembayaranService $pembayaranService,
        protected KamarService $kamarService,
        protected KosService $kosService,
        protected PenghuniKamarService $penghuniKamarService
    ) {}

    public function index()
    {
        $totalKamar = $this->kamarService->getAll()->count();
        $kamarTerisi = $this->kamarService->getTerisi()->count();
        $kamarKosong = $this->kamarService->getKosong()->count();

        $pembayarans = $this->pembayaranService->getTerverifikasi();
        $kosList = $this->kosService->getWithKamar();

        return view('admin.laporan.index', compact(
            'totalKamar',
            'kamarTerisi',
            'kamarKosong',
            'pembayarans',
            'kosList'
        ));
    }

    public function filter(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $pembayarans = $this->pembayaranService->getLaporan(
            $request->input('start'),
            $request->input('end')
        );

        return view('admin.laporan.filter', compact('pembayarans'));
    }
}
