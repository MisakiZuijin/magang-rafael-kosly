<?php

namespace App\Repositories\Contracts;

use App\Models\AturanKos;
use Illuminate\Database\Eloquent\Collection;

interface AturanKosRepositoryInterface extends BaseRepositoryInterface
{
    public function getByKos(int $kosId): Collection;
    public function getLatestByKos(int $kosId): ?AturanKos;
}
