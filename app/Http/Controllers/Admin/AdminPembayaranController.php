<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LogAktivitasService;
use App\Services\PembayaranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPembayaranController extends Controller
{
    public function __construct(
        protected PembayaranService $pembayaranService,
        protected LogAktivitasService $logAktivitasService
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
        $pembayaran = $this->pembayaranService->verify($id, [
            'status' => 'terverifikasi',
            'diverifikasi_oleh' => Auth::id(),
        ]);

        $penghuniNama = $pembayaran->penghuniKamar->penghuni->nama ?? 'Penghuni';
        $kodeKamar = $pembayaran->penghuniKamar->kamar->kode_kamar ?? '-';
        $nominal = number_format($pembayaran->jumlah, 0, ',', '.');

        $this->logAktivitasService->log(
            'verifikasi_pembayaran',
            "Mengonfirmasi pembayaran Rp {$nominal} untuk {$penghuniNama} (Kamar {$kodeKamar})"
        );

        return redirect()->back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, int $id)
    {
        $request->validate(['catatan' => 'required|string']);

        $catatan = $request->input('catatan');
        $pembayaran = $this->pembayaranService->reject($id, $catatan, Auth::id());

        $penghuniNama = $pembayaran->penghuniKamar->penghuni->nama ?? 'Penghuni';
        $nominal = number_format($pembayaran->jumlah, 0, ',', '.');

        $this->logAktivitasService->log(
            'penolakan_pembayaran',
            "Menolak pembayaran Rp {$nominal} untuk {$penghuniNama}. Catatan: {$catatan}"
        );

        return redirect()->back()->with('success', 'Pembayaran ditolak.');
    }
}
