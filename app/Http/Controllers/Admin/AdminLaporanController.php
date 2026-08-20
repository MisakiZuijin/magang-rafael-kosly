<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Services\KamarService;
use App\Services\KosService;
use App\Services\LogAktivitasService;
use App\Services\PembayaranService;
use App\Services\PenghuniKamarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLaporanController extends Controller
{
    public function __construct(
        protected PembayaranService $pembayaranService,
        protected KamarService $kamarService,
        protected KosService $kosService,
        protected PenghuniKamarService $penghuniKamarService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $totalKamar = $this->kamarService->getAll()->count();
        $kamarTerisi = $this->kamarService->getTerisi()->count();
        $kamarKosong = $this->kamarService->getKosong()->count();

        $pembayarans = $this->pembayaranService->getTerverifikasi();
        $kosList = $this->kosService->getWithKamar();
        $logs = $this->logAktivitasService->getLatest(100);

        $view = request()->is('superadmin*') ? 'superadmin.laporan.index' : 'admin.laporan.index';
        return view($view, compact(
            'totalKamar',
            'kamarTerisi',
            'kamarKosong',
            'pembayarans',
            'kosList',
            'logs'
        ));
    }

    public function filter(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $start = $request->input('start');
        $end = $request->input('end');

        $pembayarans = $this->pembayaranService->getLaporan($start, $end);
        $logs = LogAktivitas::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->with('user')
            ->latest()
            ->get();

        $view = $request->is('superadmin*') ? 'superadmin.laporan.filter' : 'admin.laporan.filter';
        return view($view, compact('pembayarans', 'logs'));
    }

    public function exportCsv(Request $request)
    {
        $start = $request->input('start', date('Y-m-01'));
        $end = $request->input('end', date('Y-m-d'));

        // Query transaksi pembayaran terverifikasi
        $pembayarans = Pembayaran::with(['penghuniKamar.penghuni', 'penghuniKamar.kamar.kos.mitra'])
            ->where('status', 'terverifikasi')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->latest()
            ->get();

        // Query log aktivitas sistem
        $logs = LogAktivitas::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->with('user')
            ->latest()
            ->get();

        $currentUser = Auth::user();
        $fileName = "laporan_kosly_" . str_replace('-', '', $start) . "_" . str_replace('-', '', $end) . ".csv";

        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        return response()->stream(function() use ($pembayarans, $logs, $start, $end, $currentUser) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Microsoft Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // Header metadata
            fputcsv($file, ['LAPORAN KEUANGAN & AKTIVITAS - KOSLY APP'], ';');
            fputcsv($file, ['Periode Laporan', $start . ' s/d ' . $end], ';');
            fputcsv($file, ['Tanggal Di-export', date('d-m-Y H:i:s')], ';');
            fputcsv($file, ['Di-export Oleh', ($currentUser->nama ?? 'User') . ' (' . strtoupper($currentUser->role ?? '-') . ')'], ';');
            fputcsv($file, [], ';');

            // Summary Block
            $totalNominal = $pembayarans->sum('jumlah');
            fputcsv($file, ['=== RINGKASAN TOTAL ==='], ';');
            fputcsv($file, ['Total Pembayaran Terverifikasi', 'Rp ' . number_format($totalNominal, 0, ',', '.')], ';');
            fputcsv($file, ['Total Transaksi Pembayaran', $pembayarans->count() . ' transaksi'], ';');
            fputcsv($file, ['Total Log Aktivitas Sistem', $logs->count() . ' catatan'], ';');
            fputcsv($file, [], ';');

            // Section 1: Detail Pembayaran Per Kos & Mitra
            fputcsv($file, ['=== DETAIL PEMBAYARAN PER KOS & MITRA ==='], ';');
            fputcsv($file, [
                'No',
                'Nama Kos',
                'Mitra / Pemilik Kos',
                'Kode Kamar',
                'Nama Penghuni',
                'Nominal (Rp)',
                'Tanggal Bayar',
                'Tanggal Verifikasi',
                'Status'
            ], ';');

            $no = 1;
            foreach ($pembayarans as $pb) {
                $kosNama = $pb->penghuniKamar->kamar->kos->nama ?? '-';
                $mitraNama = $pb->penghuniKamar->kamar->kos->mitra->nama ?? '-';
                $kodeKamar = $pb->penghuniKamar->kamar->kode_kamar ?? '-';
                $penghuniNama = $pb->penghuniKamar->penghuni->nama ?? '-';
                $tanggalBayar = $pb->tanggal_bayar ? $pb->tanggal_bayar->format('d-m-Y H:i') : '-';
                $tanggalVerif = $pb->tanggal_verifikasi ? $pb->tanggal_verifikasi->format('d-m-Y H:i') : '-';

                fputcsv($file, [
                    $no++,
                    $kosNama,
                    $mitraNama,
                    $kodeKamar,
                    $penghuniNama,
                    $pb->jumlah,
                    $tanggalBayar,
                    $tanggalVerif,
                    strtoupper($pb->status)
                ], ';');
            }

            fputcsv($file, [], ';');
            fputcsv($file, [], ';');

            // Section 2: Log Aktivitas Sistem & Pengguna
            fputcsv($file, ['=== DETAIL LOG AKTIVITAS SISTEM & PENGGUNA ==='], ';');
            fputcsv($file, [
                'No',
                'Waktu Kejadian',
                'Nama Pengguna',
                'Role Pengguna',
                'Jenis Aksi',
                'Detail Aktivitas'
            ], ';');

            $noLog = 1;
            foreach ($logs as $log) {
                $waktu = $log->created_at ? $log->created_at->format('d-m-Y H:i:s') : '-';
                $userName = $log->user->nama ?? 'Sistem';
                $userRole = strtoupper(str_replace('_', ' ', $log->user->role ?? 'System'));
                $aksi = strtoupper(str_replace('_', ' ', $log->aksi));

                fputcsv($file, [
                    $noLog++,
                    $waktu,
                    $userName,
                    $userRole,
                    $aksi,
                    $log->detail ?? '-'
                ], ';');
            }

            fclose($file);
        }, 200, $headers);
    }
}
