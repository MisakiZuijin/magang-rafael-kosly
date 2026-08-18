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

        $totalBiaya = $penghuniKamar->durasi === 'harian'
            ? ($kamar->harga_per_hari ?? 0)
            : $kamar->harga_per_bulan;

        return [
            'kos' => $kos,
            'kamar' => $kamar,
            'durasi' => $penghuniKamar->durasi,
            'total_biaya' => $totalBiaya,
            'jumlah_penghuni' => $jumlahPenghuni,
            'tanggal_keluar' => $penghuniKamar->tanggal_keluar,
        ];
    }

    public function getMitraData(int $mitraId): array
    {
        $kosList = $this->kosRepository->getByMitra($mitraId);
        $kosIds = $kosList->pluck('id');

        $kamars = $this->kamarRepository->getAll()
            ->whereIn('kos_id', $kosIds);

        return [
            'total_kos' => $kosList->count(),
            'total_kamar' => $kamars->count(),
            'kamar_kosong' => $kamars->where('status', 'kosong')->count(),
            'kamar_terisi' => $kamars->where('status', 'terisi')->count(),
            'kos_list' => $kosList->load('kamar.penghuniKamar.penghuni'),
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
