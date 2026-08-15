<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $repository
    ) {}

    public function getAllUsers(): Collection
    {
        return $this->repository->getAll();
    }

    public function getByRole(string $role): Collection
    {
        return $this->repository->getByRole($role);
    }

    public function getActiveByRole(string $role): Collection
    {
        return $this->repository->getActiveByRole($role);
    }

    public function getUserById(int $id): ?User
    {
        return $this->repository->findById($id);
    }

    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password'] ?? 'password');
        return $this->repository->create($data);
    }

    public function updateUser(int $id, array $data): User
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        return $this->repository->update($id, $data);
    }

    public function deleteUser(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function toggleActive(int $id): User
    {
        return $this->repository->toggleActive($id);
    }

    public function getPenghuniWithKamar(): Collection
    {
        return $this->repository->getPenghuniWithKamar();
    }

    public function getMitraWithKos(): Collection
    {
        return $this->repository->getMitraWithKos();
    }
}
