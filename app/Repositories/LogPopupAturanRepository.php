<?php

namespace App\Repositories;

use App\Models\LogPopupAturan;
use App\Repositories\Contracts\LogPopupAturanRepositoryInterface;
use Carbon\Carbon;

class LogPopupAturanRepository extends BaseRepository implements LogPopupAturanRepositoryInterface
{
    public function __construct(LogPopupAturan $model)
    {
        parent::__construct($model);
    }

    public function getByPenghuni(int $penghuniId): ?LogPopupAturan
    {
        return $this->model->where('penghuni_id', $penghuniId)->latest()->first();
    }

    public function getTodayByPenghuniAndKos(int $penghuniId, int $kosId): ?LogPopupAturan
    {
        return $this->model->where('penghuni_id', $penghuniId)
            ->where('kos_id', $kosId)
            ->whereDate('tanggal_popup', Carbon::today())
            ->first();
    }

    public function markAsShown(int $penghuniId, int $kosId): LogPopupAturan
    {
        return $this->model->create([
            'penghuni_id' => $penghuniId,
            'kos_id' => $kosId,
            'tanggal_popup' => Carbon::today(),
        ]);
    }
}
