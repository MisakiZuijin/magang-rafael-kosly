<?php

namespace App\Repositories;

use App\Models\Pembayaran;
use App\Repositories\Contracts\PembayaranRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PembayaranRepository extends BaseRepository implements PembayaranRepositoryInterface
{
    public function __construct(Pembayaran $model)
    {
        parent::__construct($model);
    }

    public function getByPenghuniKamar(int $penghuniKamarId): Collection
    {
        return $this->model->where('penghuni_kamar_id', $penghuniKamarId)
            ->with(['penghuniKamar.penghuni', 'penghuniKamar.kamar.kos', 'verifier'])
            ->latest()
            ->get();
    }

    public function getPending(): Collection
    {
        return $this->model->where('status', 'pending')
            ->whereNotNull('bukti_transfer_url')
            ->where('bukti_transfer_url', '!=', '')
            ->with(['penghuniKamar.penghuni', 'penghuniKamar.kamar.kos', 'verifier'])
            ->latest()
            ->get();
    }

    public function getTerverifikasi(): Collection
    {
        return $this->model->where('status', 'terverifikasi')
            ->with(['penghuniKamar.penghuni', 'penghuniKamar.kamar.kos', 'verifier'])
            ->latest()
            ->get();
    }

    public function getDitolak(): Collection
    {
        return $this->model->where('status', 'ditolak')
            ->with(['penghuniKamar.penghuni', 'penghuniKamar.kamar.kos', 'verifier'])
            ->latest()
            ->get();
    }

    public function verify(int $id, array $data): Pembayaran
    {
        $pembayaran = $this->model->findOrFail($id);
        $pembayaran->update($data);
        return $pembayaran->fresh();
    }

    public function getByKos(int $kosId): Collection
    {
        return $this->model->whereHas('penghuniKamar.kamar', function ($q) use ($kosId) {
            $q->where('kos_id', $kosId);
        })
        ->with(['penghuniKamar.penghuni', 'penghuniKamar.kamar.kos', 'verifier'])
        ->latest()
        ->get();
    }

    public function getLaporanByDateRange(string $start, string $end): Collection
    {
        $startDate = \Carbon\Carbon::parse($start)->startOfDay();
        $endDate = \Carbon\Carbon::parse($end)->endOfDay();

        return $this->model->where('status', 'terverifikasi')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->orWhereBetween('tanggal_bayar', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhereBetween('tanggal_verifikasi', [$startDate, $endDate]);
            })
            ->with(['penghuniKamar.penghuni', 'penghuniKamar.kamar.kos', 'verifier'])
            ->latest()
            ->get();
    }
}
