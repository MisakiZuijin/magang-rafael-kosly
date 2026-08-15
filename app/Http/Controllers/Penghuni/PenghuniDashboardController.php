<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AturanKosService;
use App\Services\DashboardService;
use App\Services\PembayaranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenghuniDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected PembayaranService $pembayaranService,
        protected AturanKosService $aturanKosService
    ) {}

    public function index()
    {
        $data = $this->dashboardService->getPenghuniData(Auth::id());
        return view('penghuni.dashboard', compact('data'));
    }

    public function aturan()
    {
        /** @var User $user */
        $user = Auth::user();
        $penghuniKamar = $user->penghuniKamar()->where('status', 'aktif')->first();

        if (!$penghuniKamar) {
            return redirect()->back()->with('error', 'Anda belum terdaftar di kamar manapun.');
        }

        $aturans = $this->aturanKosService->getByKos($penghuniKamar->kamar->kos_id);
        return view('penghuni.aturan', compact('aturans'));
    }

    public function dismissPopup(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $kosId = $request->input('kos_id');

        $this->aturanKosService->markPopupAsShown($user->id, $kosId);

        return response()->json(['success' => true]);
    }

    public function pembayaran()
    {
        /** @var User $user */
        $user = Auth::user();
        $penghuniKamar = $user->penghuniKamar()->where('status', 'aktif')->first();

        if (!$penghuniKamar) {
            return view('penghuni.pembayaran', ['pembayarans' => collect(), 'rekening' => null]);
        }

        $pembayarans = $this->pembayaranService->getByPenghuniKamar($penghuniKamar->id);
        $rekening = $penghuniKamar->kamar->kos;

        return view('penghuni.pembayaran', compact('pembayarans', 'rekening'));
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'pembayaran_id' => 'required|exists:pembayaran,id',
            'bukti_transfer' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $file = $request->file('bukti_transfer');

        // Simpan ke storage/app/public/images/bukti-transfer/
        $path = $file->store('images/bukti-transfer', 'public');

        $this->pembayaranService->uploadBukti(
            $request->input('pembayaran_id'),
            $path
        );

        return redirect()->back()->with('success', 'Bukti pembayaran berhasil diupload.');
    }
}
