<?php

namespace App\Repositories;

use App\Models\Kamar;
use App\Repositories\Contracts\KamarRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class KamarRepository extends BaseRepository implements KamarRepositoryInterface
{
    public function __construct(Kamar $model)
    {
        parent::__construct($model);
    }

    public function getByKos(int $kosId): Collection
    {
        return $this->model->where('kos_id', $kosId)->with(['kos.mitra', 'penghuniKamar.penghuni'])->latest()->get();
    }

    public function getKosong(): Collection
    {
        return $this->model->where('status', 'kosong')->with(['kos.mitra', 'penghuniKamar.penghuni'])->latest()->get();
    }

    public function getTerisi(): Collection
    {
        return $this->model->where('status', 'terisi')->with(['kos.mitra', 'penghuniKamar.penghuni'])->latest()->get();
    }

    public function updateStatus(int $id, string $status): Kamar
    {
        $kamar = $this->model->findOrFail($id);
        $kamar->update(['status' => $status]);
        return $kamar->fresh();
    }

    public function getByKosWithPenghuni(int $kosId): Collection
    {
        return $this->model->where('kos_id', $kosId)
            ->with(['penghuniKamar.penghuni', 'kos'])
            ->latest()
            ->get();
    }
}
