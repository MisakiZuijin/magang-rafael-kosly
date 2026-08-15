<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getAll(): Collection
    {
        return $this->model->latest()->get();
    }

    public function getPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->latest()->paginate($perPage);
    }

    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $record = $this->model->findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        $record = $this->model->findOrFail($id);
        return $record->delete();
    }

    public function findBy(array $criteria): Collection
    {
        $query = $this->model->query();
        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }
        return $query->get();
    }
}
