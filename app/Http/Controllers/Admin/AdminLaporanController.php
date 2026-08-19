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

        $view = request()->is('superadmin*') ? 'superadmin.laporan.index' : 'admin.laporan.index';
        return view($view, compact(
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

        $view = $request->is('superadmin*') ? 'superadmin.laporan.filter' : 'admin.laporan.filter';
        return view($view, compact('pembayarans'));
    }
}
