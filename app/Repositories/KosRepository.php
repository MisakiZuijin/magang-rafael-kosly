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
        return $this->model->where('mitra_id', $mitraId)->with(['mitra', 'kamar.penghuniKamar.penghuni', 'aturanKos'])->latest()->get();
    }

    public function getWithKamar(): Collection
    {
        return $this->model->with(['mitra', 'kamar.penghuniKamar.penghuni', 'aturanKos'])->latest()->get();
    }

    public function getWithKamarCount(): Collection
    {
        return $this->model->with(['mitra', 'kamar.penghuniKamar.penghuni', 'kamar.penghuniKamar.pembayaran', 'aturanKos'])->withCount(['kamar as total_kamar', 'kamar as kamar_terisi' => function ($q) {
            $q->where('status', 'terisi');
        }])->latest()->get();
    }

    public function findWithKamar(int|string $id): ?Kos
    {
        return $this->model->with(['kamar.penghuniKamar.penghuni', 'aturanKos'])
            ->where('slug', $id)
            ->orWhere('id', is_numeric($id) ? (int)$id : 0)
            ->first();
    }

    public function getAllLocations(): Collection
    {
        return $this->model->with('mitra')->get();
    }

    public function toggleLock(int|string $id): ?Kos
    {
        $kos = $this->model->where('slug', $id)->orWhere('id', is_numeric($id) ? (int)$id : 0)->first();
        if ($kos) {
            $kos->is_locked = !$kos->is_locked;
            $kos->save();
        }
        return $kos;
    }

    public function findBySlug(string $slug): ?Kos
    {
        return $this->model->where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? (int)$slug : 0)
            ->first();
    }
}
