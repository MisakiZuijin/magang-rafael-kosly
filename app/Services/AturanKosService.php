<?php

namespace App\Services;

use App\Models\AturanKos;
use App\Repositories\Contracts\AturanKosRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AturanKosService
{
    public function __construct(
        protected AturanKosRepositoryInterface $repository
    ) {}

    public function getByKos(int $kosId): Collection
    {
        return $this->repository->getByKos($kosId);
    }

    public function getLatestByKos(int $kosId): ?AturanKos
    {
        return $this->repository->getLatestByKos($kosId);
    }

    public function create(array $data): AturanKos
    {
        return $this->repository->create($data);
    }

    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    public function update(int $id, array $data): AturanKos
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
