<?php

namespace App\Repositories\Contracts;

use App\Models\Pengumuman;
use Illuminate\Database\Eloquent\Collection;

interface PengumumanRepositoryInterface extends BaseRepositoryInterface
{
    public function getByTipe(string $tipe): Collection;
    public function getByDibuatOleh(int $userId): Collection;
    public function getWithTargets(): Collection;
    public function getLatest(int $limit = 10): Collection;
}
