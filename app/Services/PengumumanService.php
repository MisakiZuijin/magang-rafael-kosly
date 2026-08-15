<?php

namespace App\Services;

use App\Models\Pengumuman;
use App\Repositories\Contracts\PengumumanRepositoryInterface;
use App\Repositories\Contracts\PengumumanTargetRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PengumumanService
{
    public function __construct(
        protected PengumumanRepositoryInterface $repository,
        protected PengumumanTargetRepositoryInterface $targetRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getWithTargets();
    }

    public function getByTipe(string $tipe): Collection
    {
        return $this->repository->getByTipe($tipe);
    }

    public function getLatest(int $limit = 10): Collection
    {
        return $this->repository->getLatest($limit);
    }

    public function getById(int $id): ?Pengumuman
    {
        return $this->repository->findById($id);
    }

    public function create(array $data, array $targets = []): Pengumuman
    {
        return DB::transaction(function () use ($data, $targets) {
            $pengumuman = $this->repository->create($data);

            foreach ($targets as $target) {
                $this->targetRepository->create([
                    'pengumuman_id' => $pengumuman->id,
                    'target_tipe' => $target['tipe'],
                    'target_id' => $target['id'],
                ]);
            }

            return $pengumuman->load('targets');
        });
    }

    public function update(int $id, array $data): Pengumuman
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
