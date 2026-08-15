<?php

namespace App\Repositories;

use App\Models\PengumumanTarget;
use App\Repositories\Contracts\PengumumanTargetRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PengumumanTargetRepository extends BaseRepository implements PengumumanTargetRepositoryInterface
{
    public function __construct(PengumumanTarget $model)
    {
        parent::__construct($model);
    }

    public function getByPengumuman(int $pengumumanId): Collection
    {
        return $this->model->where('pengumuman_id', $pengumumanId)->get();
    }

    public function getByTarget(string $tipe, int $targetId): Collection
    {
        return $this->model->where('target_tipe', $tipe)
            ->where('target_id', $targetId)
            ->with('pengumuman')
            ->latest()
            ->get();
    }
}
