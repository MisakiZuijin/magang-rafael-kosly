<?php

namespace App\Repositories\Contracts;

use App\Models\Pembayaran;
use Illuminate\Database\Eloquent\Collection;

interface PembayaranRepositoryInterface extends BaseRepositoryInterface
{
    public function getByPenghuniKamar(int $penghuniKamarId): Collection;
    public function getPending(): Collection;
    public function getTerverifikasi(): Collection;
    public function getDitolak(): Collection;
    public function verify(int $id, array $data): Pembayaran;
    public function getByKos(int $kosId): Collection;
    public function getLaporanByDateRange(string $start, string $end): Collection;
}
