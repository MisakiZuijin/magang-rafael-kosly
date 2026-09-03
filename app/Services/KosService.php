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

    public function getById(int|string $id): ?Kos
    {
        return is_numeric($id) ? $this->repository->findById((int)$id) : $this->repository->findBySlug((string)$id);
    }

    public function getDetail(int|string $id): ?Kos
    {
        return $this->repository->findWithKamar($id);
    }

    public function create(array $data): Kos
    {
        return $this->repository->create($data);
    }

    public function update(int|string $id, array $data): Kos
    {
        if (!is_numeric($id)) {
            $kos = $this->repository->findBySlug((string)$id);
            $id = $kos ? $kos->id : (int)$id;
        }
        return $this->repository->update((int)$id, $data);
    }

    public function delete(int|string $id): bool
    {
        if (!is_numeric($id)) {
            $kos = $this->repository->findBySlug((string)$id);
            $id = $kos ? $kos->id : (int)$id;
        }
        return $this->repository->delete((int)$id);
    }

    public function getAllLocations(): Collection
    {
        return $this->repository->getAllLocations();
    }

    public function toggleLock(int|string $id): ?Kos
    {
        return $this->repository->toggleLock($id);
    }
}
