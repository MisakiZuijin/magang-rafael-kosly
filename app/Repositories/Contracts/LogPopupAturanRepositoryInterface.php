<?php

namespace App\Repositories\Contracts;

use App\Models\LogPopupAturan;

interface LogPopupAturanRepositoryInterface extends BaseRepositoryInterface
{
    public function getByPenghuni(int $penghuniId): ?LogPopupAturan;
    public function getTodayByPenghuniAndKos(int $penghuniId, int $kosId): ?LogPopupAturan;
    public function markAsShown(int $penghuniId, int $kosId): LogPopupAturan;
}
