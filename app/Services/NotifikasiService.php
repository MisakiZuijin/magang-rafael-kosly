<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Repositories\Contracts\NotifikasiRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NotifikasiService
{
    public function __construct(
        protected NotifikasiRepositoryInterface $repository
    ) {}

    public function getByUser(int $userId): Collection
    {
        return $this->repository->getByUser($userId);
    }

    public function getUnread(int $userId): Collection
    {
        return $this->repository->getUnread($userId);
    }

    public function create(array $data): Notifikasi
    {
        return $this->repository->create($data);
    }

    public function markAsRead(int $id): Notifikasi
    {
        return $this->repository->markAsRead($id);
    }

    public function markAllAsRead(int $userId): bool
    {
        return $this->repository->markAllAsRead($userId);
    }

    public function sendBulk(array $userIds, string $judul, string $pesan, string $channel = 'web'): void
    {
        foreach ($userIds as $userId) {
            $this->repository->create([
                'user_id' => $userId,
                'judul' => $judul,
                'pesan' => $pesan,
                'channel' => $channel,
                'status' => 'terkirim',
            ]);
        }
    }
}
