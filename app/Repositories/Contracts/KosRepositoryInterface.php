<?php

namespace App\Repositories\Contracts;

use App\Models\Kos;
use Illuminate\Database\Eloquent\Collection;

interface KosRepositoryInterface extends BaseRepositoryInterface
{
    public function getByMitra(int $mitraId): Collection;
    public function getWithKamar(): Collection;
    public function getWithKamarCount(): Collection;
    public function findWithKamar(int|string $id): ?Kos;
    public function getAllLocations(): Collection;
    public function toggleLock(int|string $id): ?Kos;
    public function findBySlug(string $slug): ?Kos;
}
