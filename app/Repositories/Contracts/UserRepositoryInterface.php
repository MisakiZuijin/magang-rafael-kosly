<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function getByRole(string $role): Collection;
    public function getActiveByRole(string $role): Collection;
    public function toggleActive(int $id): User;
    public function getPenghuniWithKamar(): Collection;
    public function getMitraWithKos(): Collection;
}
