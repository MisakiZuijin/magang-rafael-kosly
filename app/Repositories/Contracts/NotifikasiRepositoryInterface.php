<?php

namespace App\Repositories\Contracts;

use App\Models\Notifikasi;
use Illuminate\Database\Eloquent\Collection;

interface NotifikasiRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUser(int $userId): Collection;
    public function getUnread(int $userId): Collection;
    public function markAsRead(int $id): Notifikasi;
    public function markAllAsRead(int $userId): bool;
    public function getByChannel(string $channel): Collection;
    public function getFailed(): Collection;
}
