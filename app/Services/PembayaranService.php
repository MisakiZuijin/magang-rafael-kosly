<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Repositories\Contracts\PembayaranRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PembayaranService
{
    public function __construct(
        protected PembayaranRepositoryInterface $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function getByPenghuniKamar(int $penghuniKamarId): Collection
    {
        return $this->repository->getByPenghuniKamar($penghuniKamarId);
    }

    public function getPending(): Collection
    {
        return $this->repository->getPending();
    }

    public function getTerverifikasi(): Collection
    {
        return $this->repository->getTerverifikasi();
    }

    public function getDitolak(): Collection
    {
        return $this->repository->getDitolak();
    }

    public function getById(int $id): ?Pembayaran
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): Pembayaran
    {
        return $this->repository->create($data);
    }

    public function uploadBukti(int $id, string $buktiUrl): Pembayaran
    {
        return $this->repository->update($id, [
            'bukti_transfer_url' => $buktiUrl,
            'tanggal_bayar' => now(),
            'status' => 'pending',
        ]);
    }

    public function verify(int $id, array $data): Pembayaran
    {
        $data['tanggal_verifikasi'] = now();
        return $this->repository->verify($id, $data);
    }

    public function reject(int $id, string $catatan, int $adminId): Pembayaran
    {
        return $this->repository->verify($id, [
            'status' => 'ditolak',
            'diverifikasi_oleh' => $adminId,
            'tanggal_verifikasi' => now(),
            'catatan_verifikasi' => $catatan,
        ]);
    }

    public function getLaporan(string $start, string $end): Collection
    {
        return $this->repository->getLaporanByDateRange($start, $end);
    }

    public function getByKos(int $kosId): Collection
    {
        return $this->repository->getByKos($kosId);
    }
}
