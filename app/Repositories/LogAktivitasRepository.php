<?php

namespace App\Repositories;

use App\Models\LogAktivitas;
use App\Repositories\Contracts\LogAktivitasRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LogAktivitasRepository extends BaseRepository implements LogAktivitasRepositoryInterface
{
    public function __construct(LogAktivitas $model)
    {
        parent::__construct($model);
    }

    public function getByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->with('user')->latest()->get();
    }

    public function getLatest(int $limit = 50): Collection
    {
        return $this->model->with('user')->latest()->limit($limit)->get();
    }

    public function log(string $aksi, ?string $detail = null, ?int $userId = null): LogAktivitas
    {
        return $this->model->create([
            'user_id' => $userId,
            'aksi' => $aksi,
            'detail' => $detail,
        ]);
    }
}
