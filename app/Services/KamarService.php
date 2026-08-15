<?php

namespace App\Services;

use App\Models\Kamar;
use App\Repositories\Contracts\KamarRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class KamarService
{
    public function __construct(
        protected KamarRepositoryInterface $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function getByKos(int $kosId): Collection
    {
        return $this->repository->getByKos($kosId);
    }

    public function getByKosWithPenghuni(int $kosId): Collection
    {
        return $this->repository->getByKosWithPenghuni($kosId);
    }

    public function getKosong(): Collection
    {
        return $this->repository->getKosong();
    }

    public function getTerisi(): Collection
    {
        return $this->repository->getTerisi();
    }

    public function getById(int $id): ?Kamar
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): Kamar
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Kamar
    {
        return $this->repository->update($id, $data);
    }

    public function updateStatus(int $id, string $status): Kamar
    {
        return $this->repository->updateStatus($id, $status);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
