<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kamar;
use App\Models\Kos;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Setting;
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

        $view = request()->is('superadmin*') ? 'superadmin.laporan.filter' : 'admin.laporan.filter';
        return view($view, compact('pembayarans', 'logs'));
    }

    public function exportCsv(Request $request)
    {
        $start = $request->input('start', date('Y-m-01'));
        $end = $request->input('end', date('Y-m-d'));

        // Query data transaksi pembayaran terverifikasi
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

        // Query data kos untuk rekapitulasi pendapatan per kos
        $kosList = Kos::with('mitra')->get();

        // Query data okupansi kamar kos
        $kamars = Kamar::with(['kos.mitra', 'penghuniKamar' => function($q) {
                $q->where('status', 'aktif')->with('penghuni');
            }])
            ->get();

        $currentUser = Auth::user();
        $appName = Setting::appName();
        $fileName = "Laporan_" . str_replace(' ', '_', $appName) . "_" . str_replace('-', '', $start) . "_" . str_replace('-', '', $end) . ".xls";

        return response()->streamDownload(function() use ($pembayarans, $logs, $kamars, $kosList, $start, $end, $currentUser, $appName) {
            $e = function($str) {
                return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
            };

            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
            echo '<?mso-application progid="Excel.Sheet"?>' . "\r\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\r\n";
            echo ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\r\n";
            echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\r\n";
            echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\r\n";
            echo ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\r\n";

            // Definition of Workbook Styles
            echo ' <Styles>' . "\r\n";
            echo '  <Style ss:ID="Default" ss:Name="Normal">' . "\r\n";
            echo '   <Alignment ss:Vertical="Center"/>' . "\r\n";
            echo '   <Font ss:FontName="Calibri" ss:Size="11"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo '  <Style ss:ID="TitleStyle">' . "\r\n";
            echo '   <Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#065F46"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo '  <Style ss:ID="HeaderStyle">' . "\r\n";
            echo '   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>' . "\r\n";
            echo '   <Interior ss:Color="#10B981" ss:Pattern="Solid"/>' . "\r\n";
            echo '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo '  <Style ss:ID="SectionHeader">' . "\r\n";
            echo '   <Font ss:FontName="Calibri" ss:Size="12" ss:Bold="1" ss:Color="#1E293B"/>' . "\r\n";
            echo '   <Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo '  <Style ss:ID="BoldText">' . "\r\n";
            echo '   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo '  <Style ss:ID="CurrencyStyle">' . "\r\n";
            echo '   <NumberFormat ss:Format="&#34;Rp&#34;\ #,##0"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo '  <Style ss:ID="TotalRow">' . "\r\n";
            echo '   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#065F46"/>' . "\r\n";
            echo '   <Interior ss:Color="#D1FAE5" ss:Pattern="Solid"/>' . "\r\n";
            echo '   <Alignment ss:Vertical="Center"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo '  <Style ss:ID="TotalCurrency">' . "\r\n";
            echo '   <Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#065F46"/>' . "\r\n";
            echo '   <Interior ss:Color="#D1FAE5" ss:Pattern="Solid"/>' . "\r\n";
            echo '   <NumberFormat ss:Format="&#34;Rp&#34;\ #,##0"/>' . "\r\n";
            echo '   <Alignment ss:Vertical="Center"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo '  <Style ss:ID="BadgeSuccess">' . "\r\n";
            echo '   <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#065F46"/>' . "\r\n";
            echo '   <Interior ss:Color="#D1FAE5" ss:Pattern="Solid"/>' . "\r\n";
            echo '   <Alignment ss:Horizontal="Center"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo '  <Style ss:ID="BadgeWarning">' . "\r\n";
            echo '   <Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#92400E"/>' . "\r\n";
            echo '   <Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/>' . "\r\n";
            echo '   <Alignment ss:Horizontal="Center"/>' . "\r\n";
            echo '  </Style>' . "\r\n";
            echo ' </Styles>' . "\r\n";

            // ================= SHEET 1: RINGKASAN LAPORAN =================
            echo ' <Worksheet ss:Name="Ringkasan Laporan">' . "\r\n";
            echo '  <Table ss:ExpandedColumnCount="5" x:FullColumns="1" x:FullRows="1">' . "\r\n";
            echo '   <Column ss:Width="220"/>' . "\r\n";
            echo '   <Column ss:Width="300"/>' . "\r\n";

            echo '   <Row ss:Height="30"><Cell ss:StyleID="TitleStyle"><Data ss:Type="String">LAPORAN KEUANGAN &amp; AKTIVITAS - ' . $e(strtoupper($appName)) . ' APP</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Periode Laporan</Data></Cell><Cell><Data ss:Type="String">' . $e($start . ' s/d ' . $end) . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Tanggal Di-export</Data></Cell><Cell><Data ss:Type="String">' . date('d-m-Y H:i:s') . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Di-export Oleh</Data></Cell><Cell><Data ss:Type="String">' . $e(($currentUser->nama ?? 'User') . ' (' . strtoupper($currentUser->role ?? '-') . ')') . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell><Data ss:Type="String"></Data></Cell></Row>' . "\r\n";

            $totalNominal = $pembayarans->sum('jumlah');
            $totalTerisi = $kamars->where('status', 'terisi')->count();
            $totalKosong = $kamars->where('status', 'kosong')->count();

            echo '   <Row ss:Height="24" ss:StyleID="SectionHeader"><Cell ss:MergeAcross="1"><Data ss:Type="String">RINGKASAN EKSEKUTIF</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Total Pendapatan Terverifikasi</Data></Cell><Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . $totalNominal . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Total Transaksi Pembayaran</Data></Cell><Cell><Data ss:Type="String">' . $pembayarans->count() . ' transaksi</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Jumlah Kamar Terisi</Data></Cell><Cell><Data ss:Type="String">' . $totalTerisi . ' kamar</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Jumlah Kamar Kosong</Data></Cell><Cell><Data ss:Type="String">' . $totalKosong . ' kamar</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Total Log Aktivitas Sistem</Data></Cell><Cell><Data ss:Type="String">' . $logs->count() . ' catatan</Data></Cell></Row>' . "\r\n";
            echo '  </Table>' . "\r\n";
            echo ' </Worksheet>' . "\r\n";

            // ================= SHEET 2: PENDAPATAN PER KOS =================
            echo ' <Worksheet ss:Name="Pendapatan Per Kos">' . "\r\n";
            echo '  <Table ss:ExpandedColumnCount="6" x:FullColumns="1" x:FullRows="1">' . "\r\n";
            echo '   <Column ss:Width="40"/>' . "\r\n";
            echo '   <Column ss:Width="200"/>' . "\r\n";
            echo '   <Column ss:Width="180"/>' . "\r\n";
            echo '   <Column ss:Width="110"/>' . "\r\n";
            echo '   <Column ss:Width="140"/>' . "\r\n";
            echo '   <Column ss:Width="180"/>' . "\r\n";

            echo '   <Row ss:Height="28"><Cell ss:MergeAcross="5" ss:StyleID="TitleStyle"><Data ss:Type="String">REKAPITULASI PENDAPATAN PER KOS &amp; TOTAL KESELURUHAN</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:MergeAcross="5" ss:StyleID="BoldText"><Data ss:Type="String">Periode Laporan: ' . $e($start . ' s/d ' . $end) . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell><Data ss:Type="String"></Data></Cell></Row>' . "\r\n";

            echo '   <Row ss:Height="26" ss:StyleID="HeaderStyle">' . "\r\n";
            echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Nama Kos</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Mitra / Pemilik</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Total Kamar</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Jumlah Transaksi</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Total Pendapatan (Rp)</Data></Cell>' . "\r\n";
            echo '   </Row>' . "\r\n";

            $noKos = 1;
            $grandTotalNominal = 0;
            $grandTotalTransaksi = 0;

            foreach ($kosList as $kos) {
                $kosPembayarans = $pembayarans->filter(function($pb) use ($kos) {
                    return ($pb->penghuniKamar->kamar->kos_id ?? null) === $kos->id;
                });
                $kosKamars = $kamars->filter(function($km) use ($kos) {
                    return $km->kos_id === $kos->id;
                });

                $jmlKamar = $kosKamars->count();
                $jmlTrx = $kosPembayarans->count();
                $totalTrxNominal = $kosPembayarans->sum('jumlah');

                $grandTotalNominal += $totalTrxNominal;
                $grandTotalTransaksi += $jmlTrx;

                echo '   <Row>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $noKos++ . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($kos->nama) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($kos->mitra->nama ?? '-') . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $jmlKamar . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $jmlTrx . '</Data></Cell>' . "\r\n";
                echo '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . $totalTrxNominal . '</Data></Cell>' . "\r\n";
                echo '   </Row>' . "\r\n";
            }

            // Cek jika ada transaksi yang kos-nya tidak ditemukan / terhapus
            $unmatched = $pembayarans->filter(function($pb) use ($kosList) {
                $kId = $pb->penghuniKamar->kamar->kos_id ?? null;
                return !$kId || !$kosList->contains('id', $kId);
            });
            if ($unmatched->count() > 0) {
                $unmatchedNominal = $unmatched->sum('jumlah');
                $grandTotalNominal += $unmatchedNominal;
                $grandTotalTransaksi += $unmatched->count();

                echo '   <Row>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $noKos++ . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">Lainnya / Kos Tidak Terdaftar</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">-</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">0</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $unmatched->count() . '</Data></Cell>' . "\r\n";
                echo '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . $unmatchedNominal . '</Data></Cell>' . "\r\n";
                echo '   </Row>' . "\r\n";
            }

            // Baris Total Keseluruhan
            echo '   <Row ss:Height="24" ss:StyleID="TotalRow">' . "\r\n";
            echo '    <Cell ss:MergeAcross="3" ss:StyleID="TotalRow"><Data ss:Type="String">TOTAL PENDAPATAN KESELURUHAN</Data></Cell>' . "\r\n";
            echo '    <Cell ss:StyleID="TotalRow"><Data ss:Type="Number">' . $grandTotalTransaksi . '</Data></Cell>' . "\r\n";
            echo '    <Cell ss:StyleID="TotalCurrency"><Data ss:Type="Number">' . $grandTotalNominal . '</Data></Cell>' . "\r\n";
            echo '   </Row>' . "\r\n";

            echo '  </Table>' . "\r\n";
            echo ' </Worksheet>' . "\r\n";

            // ================= SHEET 3: TRANSAKSI PEMBAYARAN =================
            echo ' <Worksheet ss:Name="Transaksi Pembayaran">' . "\r\n";
            echo '  <Table ss:ExpandedColumnCount="10" x:FullColumns="1" x:FullRows="1">' . "\r\n";
            echo '   <Column ss:Width="40"/>' . "\r\n";
            echo '   <Column ss:Width="160"/>' . "\r\n";
            echo '   <Column ss:Width="160"/>' . "\r\n";
            echo '   <Column ss:Width="90"/>' . "\r\n";
            echo '   <Column ss:Width="150"/>' . "\r\n";
            echo '   <Column ss:Width="120"/>' . "\r\n";
            echo '   <Column ss:Width="130"/>' . "\r\n";
            echo '   <Column ss:Width="120"/>' . "\r\n";
            echo '   <Column ss:Width="120"/>' . "\r\n";
            echo '   <Column ss:Width="100"/>' . "\r\n";

            echo '   <Row ss:Height="26" ss:StyleID="HeaderStyle">' . "\r\n";
            echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Nama Kos</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Mitra / Pemilik Kos</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Kode Kamar</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Nama Penghuni</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">No. HP Penghuni</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Nominal (Rp)</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Tanggal Bayar</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Tanggal Verifikasi</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Status</Data></Cell>' . "\r\n";
            echo '   </Row>' . "\r\n";

            $no = 1;
            foreach ($pembayarans as $pb) {
                $kosNama = $pb->penghuniKamar->kamar->kos->nama ?? '-';
                $mitraNama = $pb->penghuniKamar->kamar->kos->mitra->nama ?? '-';
                $kodeKamar = $pb->penghuniKamar->kamar->kode_kamar ?? '-';
                $penghuniNama = $pb->penghuniKamar->penghuni->nama ?? '-';
                $penghuniNoHp = $pb->penghuniKamar->penghuni->no_hp ?? '-';
                $tglBayar = $pb->tanggal_bayar ? $pb->tanggal_bayar->format('d-m-Y H:i') : '-';
                $tglVerif = $pb->tanggal_verifikasi ? $pb->tanggal_verifikasi->format('d-m-Y H:i') : '-';

                echo '   <Row>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $no++ . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($kosNama) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($mitraNama) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($kodeKamar) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($penghuniNama) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($penghuniNoHp) . '</Data></Cell>' . "\r\n";
                echo '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . $pb->jumlah . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($tglBayar) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($tglVerif) . '</Data></Cell>' . "\r\n";
                echo '    <Cell ss:StyleID="BadgeSuccess"><Data ss:Type="String">VERIFIKASI</Data></Cell>' . "\r\n";
                echo '   </Row>' . "\r\n";
            }

            echo '  </Table>' . "\r\n";
            echo ' </Worksheet>' . "\r\n";

            // ================= SHEET 3: OKUPANSI KOS & KAMAR =================
            echo ' <Worksheet ss:Name="Okupansi Kos &amp; Kamar">' . "\r\n";
            echo '  <Table ss:ExpandedColumnCount="8" x:FullColumns="1" x:FullRows="1">' . "\r\n";
            echo '   <Column ss:Width="40"/>' . "\r\n";
            echo '   <Column ss:Width="160"/>' . "\r\n";
            echo '   <Column ss:Width="160"/>' . "\r\n";
            echo '   <Column ss:Width="90"/>' . "\r\n";
            echo '   <Column ss:Width="100"/>' . "\r\n";
            echo '   <Column ss:Width="130"/>' . "\r\n";
            echo '   <Column ss:Width="100"/>' . "\r\n";
            echo '   <Column ss:Width="200"/>' . "\r\n";

            echo '   <Row ss:Height="26" ss:StyleID="HeaderStyle">' . "\r\n";
            echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Nama Kos</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Mitra / Pemilik Kos</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Kode Kamar</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Jenis Kamar</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Harga/Bulan (Rp)</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Status</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Penghuni Saat Ini</Data></Cell>' . "\r\n";
            echo '   </Row>' . "\r\n";

            $noKm = 1;
            foreach ($kamars as $km) {
                $kosNama = $km->kos->nama ?? '-';
                $mitraNama = $km->kos->mitra->nama ?? '-';
                $isFull = $km->status === 'terisi';
                $penghuniList = $km->penghuniKamar->pluck('penghuni.nama')->filter()->join(', ');

                echo '   <Row>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $noKm++ . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($kosNama) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($mitraNama) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($km->kode_kamar) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e(ucfirst($km->tipe)) . '</Data></Cell>' . "\r\n";
                echo '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . $km->harga_per_bulan . '</Data></Cell>' . "\r\n";
                echo '    <Cell ss:StyleID="' . ($isFull ? 'BadgeSuccess' : 'BadgeWarning') . '"><Data ss:Type="String">' . strtoupper($km->status) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($penghuniList ?: '-') . '</Data></Cell>' . "\r\n";
                echo '   </Row>' . "\r\n";
            }

            echo '  </Table>' . "\r\n";
            echo ' </Worksheet>' . "\r\n";

            // ================= SHEET 4: LOG AKTIVITAS SISTEM =================
            echo ' <Worksheet ss:Name="Log Aktivitas Sistem">' . "\r\n";
            echo '  <Table ss:ExpandedColumnCount="6" x:FullColumns="1" x:FullRows="1">' . "\r\n";
            echo '   <Column ss:Width="40"/>' . "\r\n";
            echo '   <Column ss:Width="140"/>' . "\r\n";
            echo '   <Column ss:Width="160"/>' . "\r\n";
            echo '   <Column ss:Width="120"/>' . "\r\n";
            echo '   <Column ss:Width="140"/>' . "\r\n";
            echo '   <Column ss:Width="320"/>' . "\r\n";

            echo '   <Row ss:Height="26" ss:StyleID="HeaderStyle">' . "\r\n";
            echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Waktu Kejadian</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Nama Pengguna</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Role Pengguna</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Jenis Aksi</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Detail Aktivitas</Data></Cell>' . "\r\n";
            echo '   </Row>' . "\r\n";

            $noLog = 1;
            foreach ($logs as $log) {
                $waktu = $log->created_at ? $log->created_at->format('d-m-Y H:i:s') : '-';
                $userName = $log->user->nama ?? 'Sistem';
                $userRole = strtoupper(str_replace('_', ' ', $log->user->role ?? 'System'));
                $aksi = strtoupper(str_replace('_', ' ', $log->aksi));

                echo '   <Row>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $noLog++ . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($waktu) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($userName) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($userRole) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($aksi) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($log->detail ?? '-') . '</Data></Cell>' . "\r\n";
                echo '   </Row>' . "\r\n";
            }

            echo '  </Table>' . "\r\n";
            echo ' </Worksheet>' . "\r\n";

            echo '</Workbook>' . "\r\n";
        }, $fileName, [
            "Content-Type" => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }
}
