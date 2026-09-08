<?php

namespace App\Repositories;

use App\Models\Kos;
use App\Repositories\Contracts\KosRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class KosRepository extends BaseRepository implements KosRepositoryInterface
{
    public function __construct(Kos $model)
    {
        parent::__construct($model);
    }

    public function getAll(): Collection
    {
        return $this->model->where(function ($query) {
            $query->whereNull('mitra_id')
                  ->orWhereHas('mitra', function ($q) {
                      $q->where('is_pro', false);
                  });
        })->with('mitra')->latest()->get();
    }

    public function getByMitra(int $mitraId): Collection
    {
        return $this->model->where('mitra_id', $mitraId)->with([
            'mitra',
            'aturanKos',
            'kamar.penghuniKamar.penghuni',
            'kamar.penghuniKamar.pembayaran',
        ])->latest()->get();
    }

    public function getWithKamar(): Collection
    {
        return $this->model->where(function ($query) {
            $query->whereNull('mitra_id')
                  ->orWhereHas('mitra', function ($q) {
                      $q->where('is_pro', false);
                  });
        })->with([
            'mitra',
            'aturanKos',
            'kamar.penghuniKamar.penghuni',
            'kamar.penghuniKamar.pembayaran',
        ])->latest()->get();
    }

    public function getWithKamarCount(): Collection
    {
        return $this->model->where(function ($query) {
            $query->whereNull('mitra_id')
                  ->orWhereHas('mitra', function ($q) {
                      $q->where('is_pro', false);
                  });
        })->with(['mitra', 'kamar.penghuniKamar.penghuni', 'kamar.penghuniKamar.pembayaran', 'aturanKos'])->withCount(['kamar as total_kamar', 'kamar as kamar_terisi' => function ($q) {
            $q->where('status', 'terisi');
        }])->latest()->get();
    }

    public function findWithKamar(int|string $id): ?Kos
    {
        return $this->model->with(['kamar.penghuniKamar.penghuni', 'aturanKos'])
            ->where('slug', $id)
            ->orWhere('id', is_numeric($id) ? (int)$id : 0)
            ->first();
    }

    public function getAllLocations(): Collection
    {
        return $this->model->where(function ($query) {
            $query->whereNull('mitra_id')
                  ->orWhereHas('mitra', function ($q) {
                      $q->where('is_pro', false);
                  });
        })->with('mitra')->get();
    }

    public function findBySlug(string $slug): ?Kos
    {
        return $this->model->where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? (int)$slug : 0)
            ->first();
    }
}
