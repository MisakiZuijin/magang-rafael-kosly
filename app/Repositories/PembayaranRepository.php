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
        return $this->model->where('penghuni_kamar_id', $penghuniKamarId)->latest()->get();
    }

    public function getPending(): Collection
    {
        return $this->model->where('status', 'pending')
            ->with(['penghuniKamar.penghuni', 'penghuniKamar.kamar'])
            ->latest()
            ->get();
    }

    public function getTerverifikasi(): Collection
    {
        return $this->model->where('status', 'terverifikasi')->latest()->get();
    }

    public function getDitolak(): Collection
    {
        return $this->model->where('status', 'ditolak')->latest()->get();
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
        })->latest()->get();
    }

    public function getLaporanByDateRange(string $start, string $end): Collection
    {
        return $this->model->whereBetween('created_at', [$start, $end])
            ->where('status', 'terverifikasi')
            ->with(['penghuniKamar.penghuni', 'penghuniKamar.kamar.kos'])
            ->latest()
            ->get();
    }
}
