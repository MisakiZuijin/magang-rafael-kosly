<?php

namespace App\Repositories;

use App\Models\Notifikasi;
use App\Repositories\Contracts\NotifikasiRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NotifikasiRepository extends BaseRepository implements NotifikasiRepositoryInterface
{
    public function __construct(Notifikasi $model)
    {
        parent::__construct($model);
    }

    public function getByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->latest()->get();
    }

    public function getUnread(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'terkirim')
            ->latest()
            ->get();
    }

    public function markAsRead(int $id): Notifikasi
    {
        $notif = $this->model->findOrFail($id);
        $notif->update(['status' => 'dibaca']);
        return $notif->fresh();
    }

    public function markAllAsRead(int $userId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'terkirim')
            ->update(['status' => 'dibaca']);
    }

    public function getByChannel(string $channel): Collection
    {
        return $this->model->where('channel', $channel)->latest()->get();
    }

    public function getFailed(): Collection
    {
        return $this->model->where('status', 'gagal')->latest()->get();
    }
}
