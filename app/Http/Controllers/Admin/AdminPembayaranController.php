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

        $penghuni = $pembayaran->penghuniKamar->penghuni ?? null;
        $penghuniNama = $penghuni->nama ?? 'Penghuni';
        $kodeKamar = $pembayaran->penghuniKamar->kamar->kode_kamar ?? '-';
        $kosNama = $pembayaran->penghuniKamar->kamar->kos->nama ?? 'Kos';
        $nominal = number_format($pembayaran->jumlah, 0, ',', '.');
        $notaUrl = route('pembayaran.nota', $pembayaran->kode_invoice ?? $pembayaran->id);

        // 1. Log aktivitas
        $this->logAktivitasService->log(
            'verifikasi_pembayaran',
            "Mengonfirmasi pembayaran Rp {$nominal} untuk {$penghuniNama} (Kamar {$kodeKamar})"
        );

        // 2. Notifikasi Web & WhatsApp ke Penghuni
        if ($penghuni) {
            \App\Models\Notifikasi::create([
                'user_id' => $penghuni->id,
                'judul' => 'Pembayaran Terverifikasi',
                'pesan' => "Pembayaran sebesar Rp {$nominal} untuk Kamar {$kodeKamar} di {$kosNama} telah diverifikasi.",
                'channel' => 'web',
                'status' => 'terkirim',
            ]);

            if (!empty($penghuni->no_hp) && $penghuni->no_hp !== '-') {
                $tglVerif = $pembayaran->tanggal_verifikasi ? $pembayaran->tanggal_verifikasi->format('d-m-Y H:i') : date('d-m-Y H:i');
                $waMessage = "Halo *{$penghuniNama}*,\n\n"
                    . "Pembayaran sewa kos Anda telah *BERHASIL DIVERIFIKASI* oleh admin.\n\n"
                    . "📋 *RINCIAN PEMBAYARAN:*\n"
                    . "• Kos: *{$kosNama}*\n"
                    . "• Kamar: *{$kodeKamar}*\n"
                    . "• Nominal: *Rp {$nominal}*\n"
                    . "• Waktu Verifikasi: *{$tglVerif}*\n"
                    . "• Status: *LUNAS / TERVERIFIKASI*\n\n"
                    . "Terima kasih telah melakukan pembayaran!";

                try {
                    app(\App\Services\WhatsAppService::class)->sendDirect(
                        $penghuni->no_hp,
                        'NOTIFIKASI PEMBAYARAN KOSLY',
                        $waMessage
                    );
                } catch (\Throwable $e) {
                    // Fail-safe
                }
            }
        }

        // 3. Notifikasi ke teman sekamar jika pelunasan 1 kamar penuh (100%)
        $kamar = $pembayaran->penghuniKamar->kamar ?? null;
        if ($kamar && $kamar->tipe === 'berbagi' && $pembayaran->porsi_bayar == 100) {
            $roommatePks = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                ->where('status', 'aktif')
                ->where('id', '!=', $pembayaran->penghuni_kamar_id)
                ->with('penghuni')
                ->get();

            foreach ($roommatePks as $rPk) {
                $rPenghuni = $rPk->penghuni;
                if ($rPenghuni) {
                    \App\Models\Notifikasi::create([
                        'user_id' => $rPenghuni->id,
                        'judul' => 'Pembayaran Kamar Berbagi Lunas',
                        'pesan' => "Pembayaran sewa Kamar {$kodeKamar} di {$kosNama} telah diverifikasi (dilunasi oleh {$penghuniNama}).",
                        'channel' => 'web',
                        'status' => 'terkirim',
                    ]);

                    if (!empty($rPenghuni->no_hp) && $rPenghuni->no_hp !== '-') {
                        $waRoommate = "Halo *{$rPenghuni->nama}*,\n\n"
                            . "Pembayaran sewa kos untuk Kamar *{$kodeKamar}* ({$kosNama}) telah *BERHASIL DIVERIFIKASI* oleh admin (dilunasi penuh oleh *{$penghuniNama}*).\n\n"
                            . "Status sewa kamar Anda kini sudah *LUNAS*.\n\n"
                            . "Terima kasih!";
                        try {
                            app(\App\Services\WhatsAppService::class)->sendDirect(
                                $rPenghuni->no_hp,
                                'NOTIFIKASI PEMBAYARAN KOSLY',
                                $waRoommate
                            );
                        } catch (\Throwable $e) {
                            // Fail-safe
                        }
                    }
                }
            }
        }

        return redirect()->back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function nota(string|int $id)
    {
        $pembayaran = \App\Models\Pembayaran::with([
            'penghuniKamar.penghuni',
            'penghuniKamar.kamar.kos.mitra',
            'diverifikasiOleh'
        ])->where('kode_invoice', $id)
          ->orWhere('id', is_numeric($id) ? (int)$id : 0)
          ->firstOrFail();

        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu untuk mengakses nota pembayaran.');
        }

        // 1. Validasi Otorisasi Penghuni
        if ($user->role === 'penghuni') {
            $penghuniKamar = $pembayaran->penghuniKamar;
            $isDirectOwner = ($penghuniKamar && $penghuniKamar->penghuni_id === $user->id);

            // Cek apakah teman sekamar di kamar berbagi aktif
            $isRoommate = false;
            if (!$isDirectOwner && $penghuniKamar && $penghuniKamar->kamar && $penghuniKamar->kamar->tipe === 'berbagi') {
                $isRoommate = \App\Models\PenghuniKamar::where('kamar_id', $penghuniKamar->kamar_id)
                    ->where('penghuni_id', $user->id)
                    ->where('status', 'aktif')
                    ->exists();
            }

            if (!$isDirectOwner && !$isRoommate) {
                abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk melihat nota pembayaran penghuni lain.');
            }
        }

        // 2. Validasi Otorisasi Mitra Kos
        if ($user->role === 'mitra') {
            $kos = $pembayaran->penghuniKamar->kamar->kos ?? null;
            if (!$kos || $kos->mitra_id !== $user->id) {
                abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk melihat nota pembayaran dari kos mitra lain.');
            }
        }

        // 3. Status harus terverifikasi
        if ($pembayaran->status !== 'terverifikasi') {
            return redirect()->back()->with('error', 'Nota resmi hanya dapat diakses untuk transaksi pembayaran yang telah diverifikasi.');
        }

        return view('pembayaran.nota', compact('pembayaran'));
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
