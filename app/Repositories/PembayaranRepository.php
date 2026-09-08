<?php

namespace App\Repositories;

use App\Models\Pembayaran;
use App\Repositories\Contracts\PembayaranRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PembayaranRepository extends BaseRepository implements PembayaranRepositoryInterface
{
    protected array $defaultWith = [
        'penghuniKamar.penghuni',
        'penghuniKamar.kamar.kos.mitra',
        'penghuniKamar.pembayaran',
        'diverifikasiOleh',
    ];

    public function __construct(Pembayaran $model)
    {
        parent::__construct($model);
    }

    public function getByPenghuniKamar(int $penghuniKamarId): Collection
    {
        return $this->model->where('penghuni_kamar_id', $penghuniKamarId)
            ->latest()
            ->get();
    }

    public function getAllForAdmin(): Collection
    {
        return $this->model->whereHas('penghuniKamar.kamar.kos', function ($q) {
                $q->whereNull('mitra_id')
                  ->orWhereHas('mitra', fn($m) => $m->where('is_pro', false));
            })
            ->with($this->defaultWith)
            ->latest()
            ->get();
    }

    public function getPending(): Collection
    {
        return $this->model->where('status', 'pending')
            ->whereNotNull('bukti_transfer_url')
            ->where('bukti_transfer_url', '!=', '')
            ->whereHas('penghuniKamar.kamar.kos', function ($q) {
                $q->whereNull('mitra_id')
                  ->orWhereHas('mitra', fn($m) => $m->where('is_pro', false));
            })
            ->with($this->defaultWith)
            ->latest()
            ->get();
    }

    public function getTerverifikasi(): Collection
    {
        return $this->model->where('status', 'terverifikasi')
            ->whereHas('penghuniKamar.kamar.kos', function ($q) {
                $q->whereNull('mitra_id')
                  ->orWhereHas('mitra', fn($m) => $m->where('is_pro', false));
            })
            ->with($this->defaultWith)
            ->latest()
            ->get();
    }

    public function getDitolak(): Collection
    {
        return $this->model->where('status', 'ditolak')
            ->whereHas('penghuniKamar.kamar.kos', function ($q) {
                $q->whereNull('mitra_id')
                  ->orWhereHas('mitra', fn($m) => $m->where('is_pro', false));
            })
            ->with($this->defaultWith)
            ->latest()
            ->get();
    }

    public function verify(int $id, array $data): Pembayaran
    {
        $pembayaran = $this->model->findOrFail($id);
        $pembayaran->update($data);
        return $pembayaran->fresh($this->defaultWith);
    }

    public function getByKos(int $kosId): Collection
    {
        return $this->model->whereHas('penghuniKamar.kamar', function ($q) use ($kosId) {
            $q->where('kos_id', $kosId);
        })
        ->with($this->defaultWith)
        ->latest()
        ->get();
    }

    public function getLaporanByDateRange(string $start, string $end): Collection
    {
        $startDate = \Carbon\Carbon::parse($start)->startOfDay();
        $endDate = \Carbon\Carbon::parse($end)->endOfDay();

        return $this->model->where('status', 'terverifikasi')
            ->whereHas('penghuniKamar.kamar.kos', function ($q) {
                $q->whereNull('mitra_id')
                  ->orWhereHas('mitra', fn($m) => $m->where('is_pro', false));
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->orWhereBetween('tanggal_bayar', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhereBetween('tanggal_verifikasi', [$startDate, $endDate]);
            })
            ->with($this->defaultWith)
            ->latest()
            ->get();
    }

    public function getByMitra(int $mitraId): Collection
    {
        return $this->model->whereHas('penghuniKamar.kamar.kos', function ($q) use ($mitraId) {
            $q->where('mitra_id', $mitraId);
        })
        ->with($this->defaultWith)
        ->latest()
        ->get();
    }

    public function getPendingByMitra(int $mitraId): Collection
    {
        return $this->model->where('status', 'pending')
            ->whereNotNull('bukti_transfer_url')
            ->where('bukti_transfer_url', '!=', '')
            ->whereHas('penghuniKamar.kamar.kos', function ($q) use ($mitraId) {
                $q->where('mitra_id', $mitraId);
            })
            ->with($this->defaultWith)
            ->latest()
            ->get();
    }

    public function getTerverifikasiByMitra(int $mitraId): Collection
    {
        return $this->model->where('status', 'terverifikasi')
            ->whereHas('penghuniKamar.kamar.kos', function ($q) use ($mitraId) {
                $q->where('mitra_id', $mitraId);
            })
            ->with($this->defaultWith)
            ->latest()
            ->get();
    }

    public function getDitolakByMitra(int $mitraId): Collection
    {
        return $this->model->where('status', 'ditolak')
            ->whereHas('penghuniKamar.kamar.kos', function ($q) use ($mitraId) {
                $q->where('mitra_id', $mitraId);
            })
            ->with($this->defaultWith)
            ->latest()
            ->get();
    }

    public function getLaporanByMitraAndDateRange(int $mitraId, string $start, string $end): Collection
    {
        $startDate = \Carbon\Carbon::parse($start)->startOfDay();
        $endDate = \Carbon\Carbon::parse($end)->endOfDay();

        return $this->model->where('status', 'terverifikasi')
            ->whereHas('penghuniKamar.kamar.kos', function ($q) use ($mitraId) {
                $q->where('mitra_id', $mitraId);
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->orWhereBetween('tanggal_bayar', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhereBetween('tanggal_verifikasi', [$startDate, $endDate]);
            })
            ->with($this->defaultWith)
            ->latest()
            ->get();
    }
}
