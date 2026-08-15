<?php

namespace App\Repositories\Contracts;

use App\Models\PengumumanTarget;
use Illuminate\Database\Eloquent\Collection;

interface PengumumanTargetRepositoryInterface extends BaseRepositoryInterface
{
    public function getByPengumuman(int $pengumumanId): Collection;
    public function getByTarget(string $tipe, int $targetId): Collection;
}
