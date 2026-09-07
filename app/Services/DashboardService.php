<?php

namespace App\Services;

use App\Repositories\Contracts\KamarRepositoryInterface;
use App\Repositories\Contracts\KosRepositoryInterface;
use App\Repositories\Contracts\PembayaranRepositoryInterface;
use App\Repositories\Contracts\PenghuniKamarRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected KosRepositoryInterface $kosRepository,
        protected KamarRepositoryInterface $kamarRepository,
        protected PenghuniKamarRepositoryInterface $penghuniKamarRepository,
        protected PembayaranRepositoryInterface $pembayaranRepository
    ) {}

    public function getPenghuniData(int $penghuniId): array
    {
        $penghuniKamar = $this->penghuniKamarRepository->getByPenghuni($penghuniId);

        if (!$penghuniKamar) {
            return [
                'kos' => null,
                'kamar' => null,
                'durasi' => null,
                'total_biaya' => 0,
                'jumlah_penghuni' => 0,
                'tanggal_keluar' => null,
            ];
        }

        $kamar = $penghuniKamar->kamar;
        $kos = $kamar->kos;
        $jumlahPenghuni = $this->penghuniKamarRepository->getByKamar($kamar->id)
            ->where('status', 'aktif')
            ->count();

        if ($penghuniKamar->durasi === 'harian') {
            $totalBiaya = ($kamar->harga_per_hari ?? 0) > 0 ? (float) $kamar->harga_per_hari : (float) ($kamar->harga_per_bulan ?? 0);
        } elseif ($penghuniKamar->durasi === 'mingguan') {
            $totalBiaya = ($kamar->harga_per_minggu ?? 0) > 0 ? (float) $kamar->harga_per_minggu : (float) ($kamar->harga_per_bulan ?? 0);
        } else {
            $totalBiaya = (float) ($kamar->harga_per_bulan ?? 0);
        }

        $isBerbagi = ($kamar->tipe === 'berbagi');

        $tglMasuk = $penghuniKamar->tanggal_masuk ? \Carbon\Carbon::parse($penghuniKamar->tanggal_masuk) : null;
        $tglKeluar = $penghuniKamar->tanggal_keluar ? \Carbon\Carbon::parse($penghuniKamar->tanggal_keluar) : null;
        $today = \Carbon\Carbon::now()->startOfDay();
        $isFuture = $tglMasuk && $tglMasuk->gt($today);

        $penghuniKamar->loadMissing('pembayaran');
        $statusPembayaran = $penghuniKamar->getStatusPembayaranInfo();

        return [
            'kos' => $kos,
            'kamar' => $kamar,
            'durasi' => $penghuniKamar->durasi,
            'total_biaya' => $totalBiaya,
            'is_berbagi' => $isBerbagi,
            'jumlah_penghuni' => $jumlahPenghuni,
            'tanggal_masuk' => $tglMasuk,
            'tanggal_keluar' => $tglKeluar,
            'is_future' => $isFuture,
            'sisa_hari_masuk' => ($isFuture && $tglMasuk) ? (int) $today->diffInDays($tglMasuk, false) : 0,
            'status_pembayaran' => $statusPembayaran,
            'penghuni_kamar' => $penghuniKamar,
        ];
    }

    public function getMitraData(int $mitraId): array
    {
        $kosList = $this->kosRepository->getByMitra($mitraId);
        $kosList->load('kamar.penghuniKamar.penghuni');
        $kosIds = $kosList->pluck('id');

        $kamars = $kosList->pluck('kamar')->flatten();

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $pendapatanBulanIni = \App\Models\Pembayaran::whereHas('penghuniKamar.kamar', function ($q) use ($kosIds) {
            $q->whereIn('kos_id', $kosIds);
        })
        ->where('status', 'terverifikasi')
        ->where(function ($q) use ($startOfMonth, $endOfMonth) {
            $q->whereBetween('tanggal_bayar', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
              ->orWhereBetween('tanggal_verifikasi', [$startOfMonth, $endOfMonth]);
        })
        ->sum('jumlah');

        $pendapatanTotal = \App\Models\Pembayaran::whereHas('penghuniKamar.kamar', function ($q) use ($kosIds) {
            $q->whereIn('kos_id', $kosIds);
        })
        ->where('status', 'terverifikasi')
        ->sum('jumlah');

        return [
            'total_kos' => $kosList->count(),
            'total_kamar' => $kamars->count(),
            'kamar_kosong' => $kamars->where('status', 'kosong')->count(),
            'kamar_terisi' => $kamars->where('status', 'terisi')->count(),
            'pendapatan_bulan_ini' => (int)$pendapatanBulanIni,
            'pendapatan_total' => (int)$pendapatanTotal,
            'kos_list' => $kosList,
        ];
    }

    public function getAdminData(): array
    {
        $totalKos = $this->kosRepository->getAll()->count();
        $totalKamar = $this->kamarRepository->getAll()->count();
        $kamarTerisi = $this->kamarRepository->getTerisi()->count();
        $kamarKosong = $this->kamarRepository->getKosong()->count();

        $penghuniAktif = $this->penghuniKamarRepository->getAktif();
        $pendingPayments = $this->pembayaranRepository->getPending();

        return [
            'total_kos' => $totalKos,
            'total_kamar' => $totalKamar,
            'kamar_terisi' => $kamarTerisi,
            'kamar_kosong' => $kamarKosong,
            'penghuni_aktif' => $penghuniAktif,
            'pending_payments' => $pendingPayments,
            'expired_sewa' => $this->penghuniKamarRepository->getExpired(),
        ];
    }

    public function getSuperAdminData(): array
    {
        $adminData = $this->getAdminData();

        return array_merge($adminData, [
            'total_users' => $this->userRepository->getAll()->count(),
            'total_mitra' => $this->userRepository->getByRole('mitra')->count(),
            'total_penghuni' => $this->userRepository->getByRole('penghuni')->count(),
            'total_admin' => $this->userRepository->getByRole('admin')->count(),
        ]);
    }
}
