<?php

namespace App\Repositories\Contracts;

use App\Models\LogAktivitas;
use Illuminate\Database\Eloquent\Collection;

interface LogAktivitasRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUser(int $userId): Collection;
    public function getLatest(int $limit = 50): Collection;
    public function log(string $aksi, ?string $detail = null, ?int $userId = null): LogAktivitas;
}
