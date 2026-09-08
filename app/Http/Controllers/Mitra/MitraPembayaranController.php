<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\Pembayaran;
use App\Services\LogAktivitasService;
use App\Services\PembayaranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MitraPembayaranController extends Controller
{
    public function __construct(
        protected PembayaranService $pembayaranService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $pembayarans = $this->pembayaranService->getByMitra($user->id);

        $pending = $pembayarans->where('status', 'pending')
            ->whereNotNull('bukti_transfer_url')
            ->filter(fn($p) => $p->bukti_transfer_url !== '')
            ->values();
        $terverifikasi = $pembayarans->where('status', 'terverifikasi')->values();
        $ditolak = $pembayarans->where('status', 'ditolak')->values();

        return view('mitra.pembayaran.index', compact('pending', 'terverifikasi', 'ditolak'));
    }

    public function verify(Request $request, int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $pembayaranCheck = Pembayaran::whereHas('penghuniKamar.kamar', function ($q) use ($mitraKosIds) {
            $q->whereIn('kos_id', $mitraKosIds);
        })->findOrFail($id);

        $pembayaran = $this->pembayaranService->verify($id, [
            'status' => 'terverifikasi',
            'diverifikasi_oleh' => $user->id,
        ]);

        $penghuni = $pembayaran->penghuniKamar->penghuni ?? null;
        $penghuniNama = $penghuni->nama ?? 'Penghuni';
        $kodeKamar = $pembayaran->penghuniKamar->kamar->kode_kamar ?? '-';
        $kosNama = $pembayaran->penghuniKamar->kamar->kos->nama ?? 'Kos';
        $nominal = number_format($pembayaran->jumlah, 0, ',', '.');

        $this->logAktivitasService->log(
            'verifikasi_pembayaran_mitra_pro',
            "Mitra Pro {$user->nama} mengonfirmasi pembayaran Rp {$nominal} untuk {$penghuniNama} (Kamar {$kodeKamar})"
        );

        if ($penghuni) {
            Notifikasi::create([
                'user_id' => $penghuni->id,
                'judul' => 'Pembayaran Terverifikasi',
                'pesan' => "Pembayaran sebesar Rp {$nominal} untuk Kamar {$kodeKamar} di {$kosNama} telah diverifikasi oleh pemilik kos.",
                'channel' => 'web',
                'status' => 'terkirim',
            ]);
        }

        $kamar = $pembayaran->penghuniKamar->kamar ?? null;
        if ($kamar && $kamar->tipe === 'berbagi' && $pembayaran->porsi_bayar == 100) {
            $roommatePks = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                ->where('status', 'aktif')
                ->where('id', '!=', $pembayaran->penghuni_kamar_id)
                ->get();

            foreach ($roommatePks as $roommatePk) {
                if ($roommatePk->penghuni) {
                    Notifikasi::create([
                        'user_id' => $roommatePk->penghuni->id,
                        'judul' => 'Sewa Kamar Lunas (Dibayar Teman Sekamar)',
                        'pesan' => "Pembayaran sewa Kamar {$kodeKamar} telah dilunasi oleh {$penghuniNama} dan telah diverifikasi.",
                        'channel' => 'web',
                        'status' => 'terkirim',
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, int $id)
    {
        $user = Auth::user();
        $mitraKosIds = $user->kos->pluck('id');

        $pembayaranCheck = Pembayaran::whereHas('penghuniKamar.kamar', function ($q) use ($mitraKosIds) {
            $q->whereIn('kos_id', $mitraKosIds);
        })->findOrFail($id);

        $validated = $request->validate([
            'catatan' => 'required|string|max:500',
        ]);

        $this->pembayaranService->reject($id, $validated['catatan'], $user->id);

        $penghuni = $pembayaranCheck->penghuniKamar->penghuni ?? null;
        $penghuniNama = $penghuni->nama ?? 'Penghuni';
        $kodeKamar = $pembayaranCheck->penghuniKamar->kamar->kode_kamar ?? '-';

        $this->logAktivitasService->log(
            'tolak_pembayaran_mitra_pro',
            "Mitra Pro {$user->nama} menolak pembayaran untuk {$penghuniNama} (Kamar {$kodeKamar}) dengan catatan: {$validated['catatan']}"
        );

        return redirect()->back()->with('success', 'Pembayaran telah ditolak. Form tagihan baru telah diterbitkan untuk penghuni.');
    }
}
