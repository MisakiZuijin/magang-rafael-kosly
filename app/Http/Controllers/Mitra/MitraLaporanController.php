<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Kamar;
use App\Models\Kos;
use App\Models\Pembayaran;
use App\Models\Setting;
use App\Services\KamarService;
use App\Services\KosService;
use App\Services\LogAktivitasService;
use App\Services\PembayaranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MitraLaporanController extends Controller
{
    public function __construct(
        protected PembayaranService $pembayaranService,
        protected KamarService $kamarService,
        protected KosService $kosService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $kosList = Kos::where('mitra_id', $user->id)->with('kamar.penghuniKamar.penghuni')->get();

        $allKamars = $kosList->flatMap->kamar;
        $totalKamar = $allKamars->count();
        $kamarTerisi = $allKamars->where('status', 'terisi')->count();
        $kamarKosong = $allKamars->where('status', 'kosong')->count();

        $pembayarans = $this->pembayaranService->getTerverifikasiByMitra($user->id);

        return view('mitra.laporan.index', compact(
            'totalKamar',
            'kamarTerisi',
            'kamarKosong',
            'pembayarans',
            'kosList'
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
        $user = Auth::user();

        $pembayarans = $this->pembayaranService->getLaporanByMitra($user->id, $start, $end);

        return view('mitra.laporan.filter', compact('pembayarans', 'start', 'end'));
    }

    public function exportCsv(Request $request)
    {
        $start = $request->input('start', date('Y-m-01'));
        $end = $request->input('end', date('Y-m-d'));
        $user = Auth::user();

        $kosList = Kos::where('mitra_id', $user->id)->get();
        $mitraKosIds = $kosList->pluck('id');

        $pembayarans = Pembayaran::with(['penghuniKamar.penghuni', 'penghuniKamar.kamar.kos'])
            ->where('status', 'terverifikasi')
            ->whereHas('penghuniKamar.kamar', function ($q) use ($mitraKosIds) {
                $q->whereIn('kos_id', $mitraKosIds);
            })
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->latest()
            ->get();

        $kamars = Kamar::whereIn('kos_id', $mitraKosIds)
            ->with(['kos', 'penghuniKamar' => function ($q) {
                $q->where('status', 'aktif')->with('penghuni');
            }])
            ->get();

        $appName = Setting::appName();
        $fileName = "Laporan_Mitra_" . str_replace(' ', '_', $user->nama) . "_" . str_replace('-', '', $start) . "_" . str_replace('-', '', $end) . ".xls";

        return response()->streamDownload(function () use ($pembayarans, $kamars, $kosList, $start, $end, $user, $appName) {
            $e = function ($str) {
                return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
            };

            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
            echo '<?mso-application progid="Excel.Sheet"?>' . "\r\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\r\n";
            echo ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\r\n";
            echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\r\n";
            echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\r\n";
            echo ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\r\n";

            echo ' <Styles>' . "\r\n";
            echo '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>' . "\r\n";
            echo '  <Style ss:ID="TitleStyle"><Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#065F46"/></Style>' . "\r\n";
            echo '  <Style ss:ID="HeaderStyle"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#10B981" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>' . "\r\n";
            echo '  <Style ss:ID="SectionHeader"><Font ss:FontName="Calibri" ss:Size="12" ss:Bold="1" ss:Color="#1E293B"/><Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/></Style>' . "\r\n";
            echo '  <Style ss:ID="BoldText"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1"/></Style>' . "\r\n";
            echo '  <Style ss:ID="CurrencyStyle"><NumberFormat ss:Format="&#34;Rp&#34;\ #,##0"/></Style>' . "\r\n";
            echo '  <Style ss:ID="TotalRow"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#065F46"/><Interior ss:Color="#D1FAE5" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/></Style>' . "\r\n";
            echo '  <Style ss:ID="TotalCurrency"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#065F46"/><Interior ss:Color="#D1FAE5" ss:Pattern="Solid"/><NumberFormat ss:Format="&#34;Rp&#34;\ #,##0"/><Alignment ss:Vertical="Center"/></Style>' . "\r\n";
            echo '  <Style ss:ID="BadgeSuccess"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#065F46"/><Interior ss:Color="#D1FAE5" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>' . "\r\n";
            echo '  <Style ss:ID="BadgeWarning"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#92400E"/><Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>' . "\r\n";
            echo ' </Styles>' . "\r\n";

            // SHEET 1: RINGKASAN
            echo ' <Worksheet ss:Name="Ringkasan Laporan">' . "\r\n";
            echo '  <Table ss:ExpandedColumnCount="5" x:FullColumns="1" x:FullRows="1">' . "\r\n";
            echo '   <Column ss:Width="220"/>' . "\r\n";
            echo '   <Column ss:Width="300"/>' . "\r\n";

            echo '   <Row ss:Height="30"><Cell ss:StyleID="TitleStyle"><Data ss:Type="String">LAPORAN KEUANGAN MITRA PRO - ' . $e(strtoupper($appName)) . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Nama Mitra Pro</Data></Cell><Cell><Data ss:Type="String">' . $e($user->nama) . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Periode Laporan</Data></Cell><Cell><Data ss:Type="String">' . $e($start . ' s/d ' . $end) . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Tanggal Di-export</Data></Cell><Cell><Data ss:Type="String">' . date('d-m-Y H:i:s') . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell><Data ss:Type="String"></Data></Cell></Row>' . "\r\n";

            $totalNominal = $pembayarans->sum('jumlah');
            $totalTerisi = $kamars->where('status', 'terisi')->count();
            $totalKosong = $kamars->where('status', 'kosong')->count();

            echo '   <Row ss:Height="24" ss:StyleID="SectionHeader"><Cell ss:MergeAcross="1"><Data ss:Type="String">RINGKASAN EKSEKUTIF</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Total Pendapatan Terverifikasi</Data></Cell><Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . $totalNominal . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Total Transaksi Pembayaran</Data></Cell><Cell><Data ss:Type="String">' . $pembayarans->count() . ' transaksi</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Jumlah Kamar Terisi</Data></Cell><Cell><Data ss:Type="String">' . $totalTerisi . ' kamar</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:StyleID="BoldText"><Data ss:Type="String">Jumlah Kamar Kosong</Data></Cell><Cell><Data ss:Type="String">' . $totalKosong . ' kamar</Data></Cell></Row>' . "\r\n";
            echo '  </Table>' . "\r\n";
            echo ' </Worksheet>' . "\r\n";

            // SHEET 2: REKAPITULASI PER KOS
            echo ' <Worksheet ss:Name="Pendapatan Per Kos">' . "\r\n";
            echo '  <Table ss:ExpandedColumnCount="5" x:FullColumns="1" x:FullRows="1">' . "\r\n";
            echo '   <Column ss:Width="40"/>' . "\r\n";
            echo '   <Column ss:Width="220"/>' . "\r\n";
            echo '   <Column ss:Width="110"/>' . "\r\n";
            echo '   <Column ss:Width="140"/>' . "\r\n";
            echo '   <Column ss:Width="180"/>' . "\r\n";

            echo '   <Row ss:Height="28"><Cell ss:MergeAcross="4" ss:StyleID="TitleStyle"><Data ss:Type="String">REKAPITULASI PENDAPATAN PER KOS</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell ss:MergeAcross="4" ss:StyleID="BoldText"><Data ss:Type="String">Periode Laporan: ' . $e($start . ' s/d ' . $end) . '</Data></Cell></Row>' . "\r\n";
            echo '   <Row><Cell><Data ss:Type="String"></Data></Cell></Row>' . "\r\n";

            echo '   <Row ss:Height="26" ss:StyleID="HeaderStyle">' . "\r\n";
            echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Nama Kos</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Total Kamar</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Jumlah Transaksi</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Total Pendapatan (Rp)</Data></Cell>' . "\r\n";
            echo '   </Row>' . "\r\n";

            $noKos = 1;
            $grandTotalNominal = 0;
            $grandTotalTransaksi = 0;

            foreach ($kosList as $kos) {
                $kosPembayarans = $pembayarans->filter(function ($pb) use ($kos) {
                    return ($pb->penghuniKamar->kamar->kos_id ?? null) === $kos->id;
                });
                $kosKamars = $kamars->filter(function ($km) use ($kos) {
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
                echo '    <Cell><Data ss:Type="Number">' . $jmlKamar . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $jmlTrx . '</Data></Cell>' . "\r\n";
                echo '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . $totalTrxNominal . '</Data></Cell>' . "\r\n";
                echo '   </Row>' . "\r\n";
            }

            echo '   <Row ss:Height="24" ss:StyleID="TotalRow">' . "\r\n";
            echo '    <Cell ss:MergeAcross="2" ss:StyleID="TotalRow"><Data ss:Type="String">TOTAL PENDAPATAN</Data></Cell>' . "\r\n";
            echo '    <Cell ss:StyleID="TotalRow"><Data ss:Type="Number">' . $grandTotalTransaksi . '</Data></Cell>' . "\r\n";
            echo '    <Cell ss:StyleID="TotalCurrency"><Data ss:Type="Number">' . $grandTotalNominal . '</Data></Cell>' . "\r\n";
            echo '   </Row>' . "\r\n";

            echo '  </Table>' . "\r\n";
            echo ' </Worksheet>' . "\r\n";

            // SHEET 3: RINCIAN TRANSAKSI
            echo ' <Worksheet ss:Name="Transaksi Pembayaran">' . "\r\n";
            echo '  <Table ss:ExpandedColumnCount="9" x:FullColumns="1" x:FullRows="1">' . "\r\n";
            echo '   <Column ss:Width="40"/>' . "\r\n";
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
                $kodeKamar = $pb->penghuniKamar->kamar->kode_kamar ?? '-';
                $penghuniNama = $pb->penghuniKamar->penghuni->nama ?? '-';
                $penghuniNoHp = $pb->penghuniKamar->penghuni->no_hp ?? '-';
                $tglBayar = $pb->tanggal_bayar ? $pb->tanggal_bayar->format('d-m-Y H:i') : '-';
                $tglVerif = $pb->tanggal_verifikasi ? $pb->tanggal_verifikasi->format('d-m-Y H:i') : '-';

                echo '   <Row>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $no++ . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($kosNama) . '</Data></Cell>' . "\r\n";
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

            // SHEET 4: OKUPANSI KAMAR
            echo ' <Worksheet ss:Name="Okupansi Kos &amp; Kamar">' . "\r\n";
            echo '  <Table ss:ExpandedColumnCount="7" x:FullColumns="1" x:FullRows="1">' . "\r\n";
            echo '   <Column ss:Width="40"/>' . "\r\n";
            echo '   <Column ss:Width="160"/>' . "\r\n";
            echo '   <Column ss:Width="90"/>' . "\r\n";
            echo '   <Column ss:Width="100"/>' . "\r\n";
            echo '   <Column ss:Width="130"/>' . "\r\n";
            echo '   <Column ss:Width="100"/>' . "\r\n";
            echo '   <Column ss:Width="220"/>' . "\r\n";

            echo '   <Row ss:Height="26" ss:StyleID="HeaderStyle">' . "\r\n";
            echo '    <Cell><Data ss:Type="String">No</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Nama Kos</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Kode Kamar</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Jenis Kamar</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Harga/Bulan (Rp)</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Status</Data></Cell>' . "\r\n";
            echo '    <Cell><Data ss:Type="String">Penghuni Saat Ini</Data></Cell>' . "\r\n";
            echo '   </Row>' . "\r\n";

            $noKm = 1;
            foreach ($kamars as $km) {
                $kosNama = $km->kos->nama ?? '-';
                $isFull = $km->status === 'terisi';
                $penghuniList = $km->penghuniKamar->pluck('penghuni.nama')->filter()->join(', ');

                echo '   <Row>' . "\r\n";
                echo '    <Cell><Data ss:Type="Number">' . $noKm++ . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($kosNama) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($km->kode_kamar) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e(ucfirst($km->tipe)) . '</Data></Cell>' . "\r\n";
                echo '    <Cell ss:StyleID="CurrencyStyle"><Data ss:Type="Number">' . $km->harga_per_bulan . '</Data></Cell>' . "\r\n";
                echo '    <Cell ss:StyleID="' . ($isFull ? 'BadgeSuccess' : 'BadgeWarning') . '"><Data ss:Type="String">' . strtoupper($km->status) . '</Data></Cell>' . "\r\n";
                echo '    <Cell><Data ss:Type="String">' . $e($penghuniList ?: '-') . '</Data></Cell>' . "\r\n";
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
