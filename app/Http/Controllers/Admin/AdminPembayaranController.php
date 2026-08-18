<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PembayaranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPembayaranController extends Controller
{
    public function __construct(
        protected PembayaranService $pembayaranService
    ) {}

    public function index()
    {
        $pending = $this->pembayaranService->getPending();
        $terverifikasi = $this->pembayaranService->getTerverifikasi();
        $ditolak = $this->pembayaranService->getDitolak();

        $view = request()->is('superadmin*') ? 'superadmin.pembayaran.index' : 'admin.pembayaran.index';
        return view($view, compact('pending', 'terverifikasi', 'ditolak'));
    }

    public function verify(Request $request, int $id)
    {
        $this->pembayaranService->verify($id, [
            'status' => 'terverifikasi',
            'diverifikasi_oleh' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, int $id)
    {
        $request->validate(['catatan' => 'required|string']);

        $this->pembayaranService->reject($id, $request->input('catatan'), Auth::id());

        return redirect()->back()->with('success', 'Pembayaran ditolak.');
    }
}
