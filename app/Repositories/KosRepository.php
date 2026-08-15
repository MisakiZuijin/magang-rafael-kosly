<?php

namespace App\Repositories;

use App\Models\Kos;
use App\Repositories\Contracts\KosRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class KosRepository extends BaseRepository implements KosRepositoryInterface
{
    public function __construct(Kos $model)
    {
        parent::__construct($model);
    }

    public function getByMitra(int $mitraId): Collection
    {
        return $this->model->where('mitra_id', $mitraId)->latest()->get();
    }

    public function getWithKamar(): Collection
    {
        return $this->model->with(['kamar.penghuniKamar.penghuni'])->latest()->get();
    }

    public function getWithKamarCount(): Collection
    {
        return $this->model->withCount(['kamar as total_kamar', 'kamar as kamar_terisi' => function ($q) {
            $q->where('status', 'terisi');
        }])->latest()->get();
    }

    public function findWithKamar(int $id): ?Kos
    {
        return $this->model->with(['kamar.penghuniKamar.penghuni', 'aturanKos'])->find($id);
    }

    public function getAllLocations(): Collection
    {
        return $this->model->select('id', 'nama', 'alamat', 'latitude', 'longitude')->get();
    }
}
