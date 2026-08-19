<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AturanKosService;
use App\Services\DashboardService;
use App\Services\PembayaranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\PenghuniKamarService;

class PenghuniDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected PembayaranService $pembayaranService,
        protected AturanKosService $aturanKosService,
        protected PenghuniKamarService $penghuniKamarService
    ) {}

    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $penghuniKamar = $user->penghuniKamar()->with(['kamar.kos', 'pembayaran'])->where('status', 'aktif')->first();
        if ($penghuniKamar) {
            $this->pembayaranService->checkAndGenerateAutoBilling($penghuniKamar);
        }

        $data = $this->dashboardService->getPenghuniData($user->id);
        return view('penghuni.dashboard', compact('data'));
    }

    public function aturan()
    {
        /** @var User $user */
        $user = Auth::user();
        $penghuniKamar = $user->penghuniKamar()->with(['kamar.kos'])->where('status', 'aktif')->first();

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
        $penghuniKamar = $user->penghuniKamar()->with(['kamar.kos', 'pembayaran'])->where('status', 'aktif')->first();

        if (!$penghuniKamar) {
            return view('penghuni.pembayaran', ['pembayarans' => collect(), 'rekening' => null]);
        }

        $this->pembayaranService->checkAndGenerateAutoBilling($penghuniKamar);

        $pembayarans = $this->pembayaranService->getByPenghuniKamar($penghuniKamar->id);
        $rekening = $penghuniKamar->kamar->kos;

        return view('penghuni.pembayaran', compact('pembayarans', 'rekening'));
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'pembayaran_id' => 'required|exists:pembayaran,id',
            'tipe_perpanjangan' => 'required|in:bulanan,harian',
            'jumlah_hari' => 'nullable|integer|min:1|max:365',
            'bukti_transfer' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $tipePerpanjangan = $request->input('tipe_perpanjangan', 'bulanan');
        $jumlahHari = $tipePerpanjangan === 'harian' ? (int) $request->input('jumlah_hari', 1) : 30;

        $file = $request->file('bukti_transfer');

        // Simpan ke storage/app/public/images/bukti-transfer/
        $path = $file->store('images/bukti-transfer', 'public');

        $this->pembayaranService->uploadBukti(
            $request->input('pembayaran_id'),
            $path,
            $tipePerpanjangan,
            $jumlahHari
        );

        return redirect()->back()->with('success', 'Bukti pembayaran berhasil diupload.');
    }

    public function selfCheckout()
    {
        /** @var User $user */
        $user = Auth::user();
        $penghuniKamar = $user->penghuniKamar()->with(['kamar.kos'])->where('status', 'aktif')->first();

        if (!$penghuniKamar) {
            return redirect()->back()->with('error', 'Anda tidak sedang aktif menempati kamar manapun.');
        }

        $kodeKamar = $penghuniKamar->kamar->kode_kamar ?? '-';
        $kosNama = $penghuniKamar->kamar->kos->nama ?? 'Kos';

        $this->penghuniKamarService->checkout($penghuniKamar->id);

        \App\Models\Notifikasi::create([
            'user_id' => $user->id,
            'judul' => 'Checkout Berhasil',
            'pesan' => "Anda telah berhasil melakukan checkout dari Kamar {$kodeKamar} ({$kosNama}). Terima kasih!",
            'channel' => 'web',
            'status' => 'terkirim',
        ]);

        return redirect()->route('penghuni.dashboard')->with('success', 'Berhasil checkout sewa kamar kos.');
    }
}
