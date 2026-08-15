<?php

namespace App\Repositories;

use App\Models\PenghuniKamar;
use App\Repositories\Contracts\PenghuniKamarRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PenghuniKamarRepository extends BaseRepository implements PenghuniKamarRepositoryInterface
{
    public function __construct(PenghuniKamar $model)
    {
        parent::__construct($model);
    }

    public function getAktif(): Collection
    {
        return $this->model->where('status', 'aktif')
            ->with(['kamar.kos', 'penghuni', 'pembayaran'])
            ->latest()
            ->get();
    }

    public function getByPenghuni(int $penghuniId): ?PenghuniKamar
    {
        return $this->model->where('penghuni_id', $penghuniId)
            ->where('status', 'aktif')
            ->with(['kamar.kos', 'pembayaran'])
            ->first();
    }

    public function getByKamar(int $kamarId): Collection
    {
        return $this->model->where('kamar_id', $kamarId)->latest()->get();
    }

    public function getAktifByKos(int $kosId): Collection
    {
        return $this->model->where('status', 'aktif')
            ->whereHas('kamar', function ($q) use ($kosId) {
                $q->where('kos_id', $kosId);
            })
            ->with(['kamar', 'penghuni'])
            ->latest()
            ->get();
    }

    public function getSelesai(): Collection
    {
        return $this->model->where('status', 'selesai')->latest()->get();
    }

    public function updateStatus(int $id, string $status): PenghuniKamar
    {
        $record = $this->model->findOrFail($id);
        $record->update(['status' => $status]);
        return $record->fresh();
    }

    public function getExpired(): Collection
    {
        return $this->model->where('status', 'aktif')
            ->where('tanggal_keluar', '<', now())
            ->with(['kamar.kos', 'penghuni'])
            ->get();
    }
}
