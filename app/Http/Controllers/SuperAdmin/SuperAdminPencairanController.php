<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Kos;
use App\Models\Pencairan;
use App\Models\Pembayaran;
use App\Services\LogAktivitasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminPencairanController extends Controller
{
    protected LogAktivitasService $logAktivitasService;

    public function __construct(LogAktivitasService $logAktivitasService)
    {
        $this->logAktivitasService = $logAktivitasService;
    }

    public function index(Request $request)
    {
        $bulan = (int)$request->input('bulan', date('n'));
        $tahun = (int)$request->input('tahun', date('Y'));

        // Cutoff Rule: Tanggal 1 s/d Akhir bulan (Sebelum Tgl 1 Bulan Berikutnya)
        $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->endOfDay();
        $nextMonthStart = (clone $endDate)->addSecond();

        // Eager load Kos & Mitra & count kamar dalam 1 query utama
        $kosList = Kos::with(['mitra', 'kamar'])->withCount('kamar')->get();

        // Eager load pencairan tersimpan untuk periode ini (1 query batch)
        $pencairanMap = Pencairan::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('kos_id');

        // Eager aggregate transaksi lolos cutoff periode ini berdasarkan periode sewa (periode_mulai)
        $pembayaranCurrentMap = Pembayaran::selectRaw('kamar.kos_id, COUNT(pembayaran.id) as total_transaksi, SUM(pembayaran.jumlah) as total_nominal')
            ->join('penghuni_kamar', 'pembayaran.penghuni_kamar_id', '=', 'penghuni_kamar.id')
            ->join('kamar', 'penghuni_kamar.kamar_id', '=', 'kamar.id')
            ->whereIn('pembayaran.status', ['terverifikasi', 'verified'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereNotNull('pembayaran.periode_mulai')
                      ->whereBetween('pembayaran.periode_mulai', [$startDate->toDateString(), $endDate->toDateString()]);
                })->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->whereNull('pembayaran.periode_mulai')
                       ->where(function ($q3) use ($startDate, $endDate) {
                           $q3->whereBetween('pembayaran.tanggal_verifikasi', [$startDate, $endDate])
                              ->orWhere(function ($q4) use ($startDate, $endDate) {
                                  $q4->whereNull('pembayaran.tanggal_verifikasi')
                                     ->whereBetween('pembayaran.created_at', [$startDate, $endDate]);
                              });
                       });
                });
            })
            ->groupBy('kamar.kos_id')
            ->get()
            ->keyBy('kos_id');

        // Eager aggregate transaksi ditunda / roll over ke bulan depan (periode_mulai di masa depan)
        $pembayaranDitundaMap = Pembayaran::selectRaw('kamar.kos_id, COUNT(pembayaran.id) as total_transaksi_ditunda, SUM(pembayaran.jumlah) as total_nominal_ditunda')
            ->join('penghuni_kamar', 'pembayaran.penghuni_kamar_id', '=', 'penghuni_kamar.id')
            ->join('kamar', 'penghuni_kamar.kamar_id', '=', 'kamar.id')
            ->whereIn('pembayaran.status', ['terverifikasi', 'verified'])
            ->where(function ($query) use ($nextMonthStart) {
                $query->where(function ($q) use ($nextMonthStart) {
                    $q->whereNotNull('pembayaran.periode_mulai')
                      ->where('pembayaran.periode_mulai', '>=', $nextMonthStart->toDateString());
                })->orWhere(function ($q2) use ($nextMonthStart) {
                    $q2->whereNull('pembayaran.periode_mulai')
                       ->where(function ($q3) use ($nextMonthStart) {
                           $q3->where('pembayaran.tanggal_verifikasi', '>=', $nextMonthStart)
                              ->orWhere(function ($q4) use ($nextMonthStart) {
                                  $q4->whereNull('pembayaran.tanggal_verifikasi')
                                     ->where('pembayaran.created_at', '>=', $nextMonthStart);
                              });
                       });
                });
            })
            ->groupBy('kamar.kos_id')
            ->get()
            ->keyBy('kos_id');

        $pencairanData = collect();
        $totalPendapatanSemua = 0;
        $totalSudahDicairkan = 0;
        $totalBelumDicairkan = 0;
        $totalPendapatanDitundaSemua = 0;

        // Iterasi in-memory tanpa query tambahan (0 N+1 Queries)
        foreach ($kosList as $kos) {
            $currentData = $pembayaranCurrentMap->get($kos->id);
            $totalTransaksi = $currentData ? (int)$currentData->total_transaksi : 0;
            $totalNominal = $currentData ? (int)$currentData->total_nominal : 0;

            $ditundaData = $pembayaranDitundaMap->get($kos->id);
            $totalTransaksiDitunda = $ditundaData ? (int)$ditundaData->total_transaksi_ditunda : 0;
            $totalNominalDitunda = $ditundaData ? (int)$ditundaData->total_nominal_ditunda : 0;

            $recordPencairan = $pencairanMap->get($kos->id);
            $status = $recordPencairan ? $recordPencairan->status : 'pending';
            $nominalCair = $recordPencairan ? (int)$recordPencairan->total_pendapatan : $totalNominal;

            $pencairanData->push([
                'kos' => $kos,
                'mitra' => $kos->mitra,
                'total_transaksi' => $totalTransaksi,
                'total_nominal' => $nominalCair,
                'total_transaksi_ditunda' => $totalTransaksiDitunda,
                'total_nominal_ditunda' => $totalNominalDitunda,
                'status' => $status,
                'record' => $recordPencairan,
            ]);

            $totalPendapatanSemua += $nominalCair;
            $totalPendapatanDitundaSemua += $totalNominalDitunda;
            if ($status === 'dicairkan') {
                $totalSudahDicairkan += $nominalCair;
            } else {
                $totalBelumDicairkan += $nominalCair;
            }
        }

        return view('superadmin.pencairan.index', compact(
            'pencairanData',
            'bulan',
            'tahun',
            'totalPendapatanSemua',
            'totalSudahDicairkan',
            'totalBelumDicairkan',
            'totalPendapatanDitundaSemua'
        ));
    }

    public function proses(Request $request)
    {
        $request->validate([
            'kos_id' => 'required|exists:kos,id',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2099',
            'catatan' => 'nullable|string|max:1000',
            'bukti_transfer' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $kos = Kos::with('mitra')->findOrFail($request->kos_id);
        $bulan = (int)$request->bulan;
        $tahun = (int)$request->tahun;

        // Recalculate verified revenue for safety based on periode_mulai
        $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->endOfDay();

        $totalNominal = (int)Pembayaran::whereHas('penghuniKamar.kamar', function ($q) use ($kos) {
            $q->where('kos_id', $kos->id);
        })
        ->whereIn('status', ['terverifikasi', 'verified'])
        ->where(function ($query) use ($startDate, $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereNotNull('periode_mulai')
                  ->whereBetween('periode_mulai', [$startDate->toDateString(), $endDate->toDateString()]);
            })->orWhere(function ($q2) use ($startDate, $endDate) {
                $q2->whereNull('periode_mulai')
                   ->where(function ($q3) use ($startDate, $endDate) {
                       $q3->whereBetween('tanggal_verifikasi', [$startDate, $endDate])
                          ->orWhere(function ($q4) use ($startDate, $endDate) {
                              $q4->whereNull('tanggal_verifikasi')
                                 ->whereBetween('created_at', [$startDate, $endDate]);
                          });
                   });
            });
        })
        ->sum('jumlah');

        $buktiPath = null;
        if ($request->hasFile('bukti_transfer')) {
            $buktiPath = $request->file('bukti_transfer')->store('images/pencairan', 'public');
        }

        $pencairan = Pencairan::updateOrCreate(
            [
                'kos_id' => $kos->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
            [
                'mitra_id' => $kos->mitra_id,
                'total_pendapatan' => $totalNominal,
                'status' => 'dicairkan',
                'tanggal_cair' => now(),
                'catatan' => $request->catatan,
                'dicairkan_oleh' => Auth::id(),
            ]
        );

        if ($buktiPath) {
            $pencairan->update(['bukti_transfer' => $buktiPath]);
        }

        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM');
        $nominalFormatted = number_format($totalNominal, 0, ',', '.');
        $this->logAktivitasService->log('tambah_pencairan', "Mencairkan pendapatan Kos {$kos->nama} (Periode {$namaBulan} {$tahun}) sebesar Rp {$nominalFormatted} ke Mitra {$kos->mitra->nama}");

        return redirect()->back()->with('success', "Pencairan pendapatan Kos {$kos->nama} sebesar Rp {$nominalFormatted} berhasil diproses.");
    }
}
