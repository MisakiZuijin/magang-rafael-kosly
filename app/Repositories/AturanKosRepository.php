<?php

namespace App\Repositories;

use App\Models\AturanKos;
use App\Repositories\Contracts\AturanKosRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AturanKosRepository extends BaseRepository implements AturanKosRepositoryInterface
{
    public function __construct(AturanKos $model)
    {
        parent::__construct($model);
    }

    public function getByKos(int $kosId): Collection
    {
        return $this->model->where('kos_id', $kosId)->latest()->get();
    }

    public function getLatestByKos(int $kosId): ?AturanKos
    {
        return $this->model->where('kos_id', $kosId)->latest()->first();
    }
}
