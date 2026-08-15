<?php

namespace App\Repositories;

use App\Models\Pengumuman;
use App\Repositories\Contracts\PengumumanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PengumumanRepository extends BaseRepository implements PengumumanRepositoryInterface
{
    public function __construct(Pengumuman $model)
    {
        parent::__construct($model);
    }

    public function getByTipe(string $tipe): Collection
    {
        return $this->model->where('tipe', $tipe)->latest()->get();
    }

    public function getByDibuatOleh(int $userId): Collection
    {
        return $this->model->where('dibuat_oleh', $userId)->latest()->get();
    }

    public function getWithTargets(): Collection
    {
        return $this->model->with(['targets', 'dibuatOleh'])->latest()->get();
    }

    public function getLatest(int $limit = 10): Collection
    {
        return $this->model->with(['targets'])->latest()->limit($limit)->get();
    }
}
