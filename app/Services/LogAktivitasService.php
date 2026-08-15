<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Repositories\Contracts\LogAktivitasRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LogAktivitasService
{
    public function __construct(
        protected LogAktivitasRepositoryInterface $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function getLatest(int $limit = 50): Collection
    {
        return $this->repository->getLatest($limit);
    }

    public function getByUser(int $userId): Collection
    {
        return $this->repository->getByUser($userId);
    }

    public function log(string $aksi, ?string $detail = null, ?int $userId = null): LogAktivitas
    {
        return $this->repository->log($aksi, $detail, $userId);
    }
}
