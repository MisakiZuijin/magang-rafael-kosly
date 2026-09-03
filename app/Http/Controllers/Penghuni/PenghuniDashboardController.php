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
        // Real-time check and notify overdue leases (H-7 and Jatuh Tempo)
        $this->penghuniKamarService->periksaSemuaNotifikasiSewa();

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

    public function pembayaran()
    {
        $this->penghuniKamarService->periksaSemuaNotifikasiSewa();

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
                'roommateFullPending' => false,
                'roommatePendingName' => '',
                'roommatePendingTime' => '',
                'onlyHalfOption' => false,
                'roommateHalfName' => '',
            ]);
        }

        $this->pembayaranService->checkAndGenerateAutoBilling($penghuniKamar);

        $pembayarans = $this->pembayaranService->getByPenghuniKamar($penghuniKamar->id);
        $rekening = $penghuniKamar->kamar->kos;
        $kamar = $penghuniKamar->kamar;
        $isKamarBerbagi = ($kamar && $kamar->tipe === 'berbagi');

        $activePenghuniCount = $kamar
            ? \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)->where('status', 'aktif')->count()
            : 1;

        $myPending = $pembayarans->where('status', 'pending')->first();
        $myPendingWithProof = $myPending && !empty($myPending->bukti_transfer_url);

        $roommateFullPaid = false;
        $roommateName = '';
        $roommateFullPending = false;
        $roommatePendingName = '';
        $roommatePendingTime = '';
        $roommatePendingBuktiUrl = '';
        $roommatePendingJumlah = 0;
        $onlyHalfOption = false;
        $roommateHalfName = '';
        $roommateHalfTipe = 'bulanan';
        $roommateHalfDays = 30;
        $roommateHalfJumlah = 0;

        if ($isKamarBerbagi) {
            $roommatePks = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                ->where('status', 'aktif')
                ->where('id', '!=', $penghuniKamar->id)
                ->with('penghuni')
                ->get();

            // 1. Cek apakah ada pembayaran FULL (100%) yang diunggah oleh teman sekamar dan sedang PENDING
            // PENTING: Jika ada teman sekamar yang sudah upload bukti full, tampilkan info bahwa sedang menunggu verifikasi admin
            if (!$myPendingWithProof) {
                foreach ($roommatePks as $rPk) {
                    $rPendingFull = \App\Models\Pembayaran::where('penghuni_kamar_id', $rPk->id)
                        ->where('status', 'pending')
                        ->whereNotNull('bukti_transfer_url')
                        ->where('bukti_transfer_url', '!=', '')
                        ->where('porsi_bayar', 100)
                        ->latest()
                        ->first();

                    if ($rPendingFull) {
                        $roommateFullPending = true;
                        $roommatePendingName = $rPk->penghuni->nama ?? 'Teman Sekamar';
                        $roommatePendingBuktiUrl = $rPendingFull->bukti_transfer_url;
                        $roommatePendingJumlah = $rPendingFull->jumlah;

                        if ($rPendingFull->catatan_verifikasi && preg_match('/pada (.+)\)/u', $rPendingFull->catatan_verifikasi, $m)) {
                            $roommatePendingTime = trim($m[1]);
                        } elseif ($rPendingFull->tanggal_bayar) {
                            $roommatePendingTime = \Carbon\Carbon::parse($rPendingFull->tanggal_bayar)->locale('id')->isoFormat('D MMMM Y');
                        } elseif ($rPendingFull->updated_at) {
                            $roommatePendingTime = $rPendingFull->updated_at->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';
                        } else {
                            $roommatePendingTime = '-';
                        }
                        break;
                    }
                }
            }

            // 2. Cek apakah pelunasan penuh terverifikasi dari teman sekamar HANYA jika TIDAK ADA tagihan pending baru!
            // Jika ada tagihan pending baru (misal tagihan perpanjangan sewa), seluruh teman sekamar bisa melihat form dan membayarnya!
            if (!$myPending && !$myPendingWithProof && !$roommateFullPending) {
                $coveredPayment = \App\Models\Pembayaran::where('penghuni_kamar_id', $penghuniKamar->id)
                    ->where('status', 'terverifikasi')
                    ->where('catatan_verifikasi', 'LIKE', 'Lunas (Dibayar%oleh%')
                    ->latest()
                    ->first();

                if ($coveredPayment) {
                    $roommateFullPaid = true;
                    $catatan = $coveredPayment->catatan_verifikasi;
                    $roommateName = trim(preg_replace('/^Lunas \(Dibayar (?:Full|Tarif (?:1 Kamar|2 Orang|3 Orang)) oleh (.+)\)$/', '$1', $catatan));
                }
            }

            // 3. Cek apakah ada teman sekamar yang sedang membayar SETENGAH (50%) dan pending di kamar isi 2 orang
            if ($activePenghuniCount <= 2 && !$roommateFullPaid && !$roommateFullPending && !$myPendingWithProof) {
                foreach ($roommatePks as $rPk) {
                    $rHalf = \App\Models\Pembayaran::where('penghuni_kamar_id', $rPk->id)
                        ->where('status', 'pending')
                        ->whereNotNull('bukti_transfer_url')
                        ->where('bukti_transfer_url', '!=', '')
                        ->where('porsi_bayar', 50)
                        ->latest()
                        ->first();

                    if ($rHalf) {
                        $onlyHalfOption = true;
                        $roommateHalfName = $rPk->penghuni->nama ?? 'Teman Sekamar';
                        $roommateHalfTipe = $rHalf->tipe_perpanjangan ?? ($rHalf->jumlah_hari == 1 || ($rHalf->jumlah_hari > 0 && $rHalf->jumlah_hari != 7 && $rHalf->jumlah_hari != 30) ? 'harian' : ($rHalf->jumlah_hari == 7 ? 'mingguan' : 'bulanan'));
                        $roommateHalfDays = (int) ($rHalf->jumlah_hari ?: ($roommateHalfTipe === 'harian' ? 1 : ($roommateHalfTipe === 'mingguan' ? 7 : 30)));
                        $roommateHalfJumlah = (float) $rHalf->jumlah;
                        break;
                    }
                }
            }
        }

        $myVerifiedInitial = $pembayarans->where('status', 'terverifikasi')->isNotEmpty();
        $roommateUnpaidInitial = false;
        $roommateUnpaidName = '';
        $canRenew = true;

        if ($isKamarBerbagi && $activePenghuniCount <= 2) {
            foreach ($roommatePks as $rPk) {
                $rHasVerified = \App\Models\Pembayaran::where('penghuni_kamar_id', $rPk->id)
                    ->where('status', 'terverifikasi')
                    ->exists();

                if (!$rHasVerified) {
                    $roommateUnpaidInitial = true;
                    $roommateUnpaidName = $rPk->penghuni->nama ?? 'Rekan Sekamar';
                    $canRenew = false;
                    break;
                }
            }
        }

        $activeCount = $activePenghuniCount;

        return view('penghuni.pembayaran', compact(
            'pembayarans',
            'penghuniKamar',
            'rekening',
            'isKamarBerbagi',
            'activePenghuniCount',
            'activeCount',
            'roommateFullPaid',
            'roommateName',
            'roommateFullPending',
            'roommatePendingName',
            'roommatePendingTime',
            'roommatePendingBuktiUrl',
            'roommatePendingJumlah',
            'onlyHalfOption',
            'roommateHalfName',
            'roommateHalfTipe',
            'roommateHalfDays',
            'roommateHalfJumlah',
            'myVerifiedInitial',
            'roommateUnpaidInitial',
            'roommateUnpaidName',
            'canRenew'
        ));
    }

    public function uploadBukti(Request $request)
    {
        $request->validate([
            'pembayaran_id' => 'nullable',
            'tipe_perpanjangan' => 'required|in:bulanan,mingguan,harian',
            'porsi_bayar' => 'nullable|in:100,50',
            'jumlah_hari' => 'nullable|integer|min:1|max:365',
            'bukti_transfer' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $penghuniKamarIds = $user->penghuniKamar()->pluck('id')->toArray();

        if (empty($penghuniKamarIds)) {
            return redirect()->back()->with('error', 'Anda belum terdaftar di kamar manapun.');
        }

        $pembayaranId = $request->input('pembayaran_id');
        if ($pembayaranId === 'new' || empty($pembayaranId)) {
            $activePk = $user->penghuniKamar()->where('status', 'aktif')->latest()->first();
            if (!$activePk) {
                return redirect()->back()->with('error', 'Data kamar aktif tidak ditemukan.');
            }

            $pembayaran = \App\Models\Pembayaran::create([
                'penghuni_kamar_id' => $activePk->id,
                'jumlah' => 0,
                'porsi_bayar' => (int) $request->input('porsi_bayar', 100),
                'tipe_perpanjangan' => $request->input('tipe_perpanjangan', 'bulanan'),
                'status' => 'pending',
                'periode_mulai' => $activePk->tanggal_keluar ? \Carbon\Carbon::parse($activePk->tanggal_keluar)->toDateString() : now()->toDateString(),
                'periode_selesai' => $activePk->tanggal_keluar ? \Carbon\Carbon::parse($activePk->tanggal_keluar)->addMonth()->toDateString() : now()->addMonth()->toDateString(),
            ]);
        } else {
            // Proteksi Keamanan IDOR: Pastikan tagihan milik kamar penghuni yang sedang login
            $pembayaran = \App\Models\Pembayaran::where('id', $pembayaranId)
                ->whereIn('penghuni_kamar_id', $penghuniKamarIds)
                ->first();

            if (!$pembayaran) {
                return redirect()->back()->with('error', 'Tagihan pembayaran tidak ditemukan atau bukan milik Anda.');
            }
        }

        $tipePerpanjangan = $request->input('tipe_perpanjangan', 'bulanan');
        $porsiBayar = (int) $request->input('porsi_bayar', 100);
        $jumlahHari = $tipePerpanjangan === 'harian' ? (int) $request->input('jumlah_hari', 1) : ($tipePerpanjangan === 'mingguan' ? 7 : 30);

        $file = $request->file('bukti_transfer');
        $path = $file->store('images/bukti-transfer', 'public');

        $pb = $this->pembayaranService->uploadBukti(
            $pembayaran->id,
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
