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
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        // Hapus log aktivitas, notifikasi, dan log aturan terkait user dari database
        \App\Models\LogAktivitas::where('user_id', $id)->delete();
        \App\Models\Notifikasi::where('user_id', $id)->delete();
        \App\Models\LogPopupAturan::where('penghuni_id', $id)->delete();

        // Jika user yang dihapus adalah anak kos (penghuni), bersihkan penempatan kamar & perbarui status kamar
        if ($user->role === 'penghuni') {
            $pks = \App\Models\PenghuniKamar::where('penghuni_id', $id)->get();
            foreach ($pks as $pk) {
                $kamarId = $pk->kamar_id;
                \App\Models\Pembayaran::where('penghuni_kamar_id', $pk->id)->delete();
                $pk->delete();

                $remaining = \App\Models\PenghuniKamar::where('kamar_id', $kamarId)->where('status', 'aktif')->count();
                if ($remaining === 0) {
                    \App\Models\Kamar::where('id', $kamarId)->update(['status' => 'kosong']);
                }
            }
        }

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
