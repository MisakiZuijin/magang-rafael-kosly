<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function getByRole(string $role): Collection
    {
        return $this->model->where('role', $role)->latest()->get();
    }

    public function getActiveByRole(string $role): Collection
    {
        return $this->model->where('role', $role)->where('is_active', true)->latest()->get();
    }

    public function toggleActive(int $id): User
    {
        $user = $this->model->findOrFail($id);
        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        return $user->fresh();
    }

    public function getPenghuniWithKamar(): Collection
    {
        return $this->model->where('role', 'penghuni')
            ->with(['penghuniKamar.kamar.kos', 'penghuniKamar.pembayaran'])
            ->latest()
            ->get();
    }

    public function getMitraWithKos(): Collection
    {
        return $this->model->where('role', 'mitra')
            ->with(['kos.kamar'])
            ->latest()
            ->get();
    }
}
