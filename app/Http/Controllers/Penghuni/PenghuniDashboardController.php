<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AturanKosService;
use App\Services\DashboardService;
use App\Services\LogAktivitasService;
use App\Services\PembayaranService;
use App\Services\PenghuniKamarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenghuniDashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected PembayaranService $pembayaranService,
        protected AturanKosService $aturanKosService,
        protected PenghuniKamarService $penghuniKamarService,
        protected LogAktivitasService $logAktivitasService
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
            return view('penghuni.pembayaran', [
                'pembayarans' => collect(),
                'rekening' => null,
                'isKamarBerbagi' => false,
                'roommateFullPaid' => false,
                'roommateName' => '',
            ]);
        }

        $this->pembayaranService->checkAndGenerateAutoBilling($penghuniKamar);

        $pembayarans = $this->pembayaranService->getByPenghuniKamar($penghuniKamar->id);
        $rekening = $penghuniKamar->kamar->kos;
        $kamar = $penghuniKamar->kamar;
        $isKamarBerbagi = ($kamar && $kamar->tipe === 'berbagi');

        $roommateFullPaid = false;
        $roommateName = '';

        if ($isKamarBerbagi) {
            // Cek apakah penghuni saat ini memiliki tagihan yang sedang pending
            $hasPendingPayment = \App\Models\Pembayaran::where('penghuni_kamar_id', $penghuniKamar->id)
                ->where('status', 'pending')
                ->exists();

            // Banner 'Pembayaran Kamar Lunas - Dilunasi Full oleh Teman' HANYA tampil jika TIDAK ada tagihan pending yang sedang berjalan
            if (!$hasPendingPayment) {
                $coveredPayment = \App\Models\Pembayaran::where('penghuni_kamar_id', $penghuniKamar->id)
                    ->where('status', 'terverifikasi')
                    ->where('catatan_verifikasi', 'LIKE', 'Lunas (Dibayar%oleh%')
                    ->latest()
                    ->first();

                if ($coveredPayment) {
                    $roommateFullPaid = true;
                    $catatan = $coveredPayment->catatan_verifikasi;
                    $roommateName = trim(preg_replace('/^Lunas \(Dibayar (?:Full|Tarif 2 Orang) oleh (.+)\)$/', '$1', $catatan));
                }
            }
        }

        return view('penghuni.pembayaran', compact('pembayarans', 'rekening', 'isKamarBerbagi', 'roommateFullPaid', 'roommateName'));
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'pembayaran_id' => 'required|exists:pembayaran,id',
            'tipe_perpanjangan' => 'required|in:bulanan,mingguan,harian',
            'porsi_bayar' => 'nullable|in:100,50',
            'jumlah_hari' => 'nullable|integer|min:1|max:365',
            'bukti_transfer' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $tipePerpanjangan = $request->input('tipe_perpanjangan', 'bulanan');
        $porsiBayar = (int) $request->input('porsi_bayar', 100);
        $jumlahHari = $tipePerpanjangan === 'harian' ? (int) $request->input('jumlah_hari', 1) : ($tipePerpanjangan === 'mingguan' ? 7 : 30);

        $file = $request->file('bukti_transfer');
        $path = $file->store('images/bukti-transfer', 'public');

        $pb = $this->pembayaranService->uploadBukti(
            $request->input('pembayaran_id'),
            $path,
            $tipePerpanjangan,
            $jumlahHari,
            $porsiBayar
        );

        $nominal = number_format($pb->jumlah, 0, ',', '.');
        $this->logAktivitasService->log('upload_bukti_pembayaran', "Penghuni " . Auth::user()->nama . " mengunggah bukti pembayaran Rp {$nominal}");

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

        $this->logAktivitasService->log('checkout_penghuni', "Penghuni {$user->nama} melakukan checkout mandiri dari Kamar {$kodeKamar} ({$kosNama})");

        return redirect()->route('penghuni.dashboard')->with('success', 'Berhasil checkout sewa kamar kos.');
    }
}
