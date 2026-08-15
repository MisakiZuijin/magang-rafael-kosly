<?php

namespace App\Services;

use App\Models\Kos;
use App\Repositories\Contracts\KosRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class KosService
{
    public function __construct(
        protected KosRepositoryInterface $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function getWithKamar(): Collection
    {
        return $this->repository->getWithKamar();
    }

    public function getWithKamarCount(): Collection
    {
        return $this->repository->getWithKamarCount();
    }

    public function getByMitra(int $mitraId): Collection
    {
        return $this->repository->getByMitra($mitraId);
    }

    public function getById(int $id): ?Kos
    {
        return $this->repository->findById($id);
    }

    public function getDetail(int $id): ?Kos
    {
        return $this->repository->findWithKamar($id);
    }

    public function create(array $data): Kos
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Kos
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getAllLocations(): Collection
    {
        return $this->repository->getAllLocations();
    }
}
