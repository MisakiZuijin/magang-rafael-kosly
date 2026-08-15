<?php

namespace App\Repositories\Contracts;

use App\Models\Kamar;
use Illuminate\Database\Eloquent\Collection;

interface KamarRepositoryInterface extends BaseRepositoryInterface
{
    public function getByKos(int $kosId): Collection;
    public function getKosong(): Collection;
    public function getTerisi(): Collection;
    public function updateStatus(int $id, string $status): Kamar;
    public function getByKosWithPenghuni(int $kosId): Collection;
}
