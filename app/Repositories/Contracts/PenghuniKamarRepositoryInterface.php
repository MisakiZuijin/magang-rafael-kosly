<?php

namespace App\Repositories\Contracts;

use App\Models\PenghuniKamar;
use Illuminate\Database\Eloquent\Collection;

interface PenghuniKamarRepositoryInterface extends BaseRepositoryInterface
{
    public function getAktif(): Collection;
    public function getByPenghuni(int $penghuniId): ?PenghuniKamar;
    public function getByKamar(int $kamarId): Collection;
    public function getAktifByKos(int $kosId): Collection;
    public function getSelesai(): Collection;
    public function updateStatus(int $id, string $status): PenghuniKamar;
    public function getExpired(): Collection;
}
