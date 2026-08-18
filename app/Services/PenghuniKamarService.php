<?php

namespace App\Services;

use App\Models\Kamar;
use App\Models\PenghuniKamar;
use App\Repositories\Contracts\KamarRepositoryInterface;
use App\Repositories\Contracts\PenghuniKamarRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PenghuniKamarService
{
    public function __construct(
        protected PenghuniKamarRepositoryInterface $repository,
        protected KamarRepositoryInterface $kamarRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function getAktif(): Collection
    {
        return $this->repository->getAktif();
    }

    public function getByPenghuni(int $penghuniId): ?PenghuniKamar
    {
        return $this->repository->getByPenghuni($penghuniId);
    }

    public function getByKamar(int $kamarId): Collection
    {
        return $this->repository->getByKamar($kamarId);
    }

    public function getAktifByKos(int $kosId): Collection
    {
        return $this->repository->getAktifByKos($kosId);
    }

    public function getExpired(): Collection
    {
        return $this->repository->getExpired();
    }

    public function getById(int $id): ?PenghuniKamar
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): PenghuniKamar
    {
        $penghuniKamar = $this->repository->create($data);

        // Update status kamar jika penuh
        $kamar = $this->kamarRepository->findById($data['kamar_id']);
        $jumlahPenghuni = $this->repository->getByKamar($data['kamar_id'])->where('status', 'aktif')->count();

        if ($jumlahPenghuni >= $kamar->kapasitas) {
            $this->kamarRepository->updateStatus($data['kamar_id'], 'terisi');
        }

        // --- BUAT TAGIHAN PEMBAYARAN AWAL AUTOMATIS ---
        $jumlahBiaya = $penghuniKamar->durasi === 'harian'
            ? ($kamar->harga_per_hari ?? 0)
            : $kamar->harga_per_bulan;

        $tanggalKeluar = $penghuniKamar->tanggal_keluar
            ? \Carbon\Carbon::parse($penghuniKamar->tanggal_keluar)
            : \Carbon\Carbon::parse($penghuniKamar->tanggal_masuk)->addMonth();

        \App\Models\Pembayaran::create([
            'penghuni_kamar_id' => $penghuniKamar->id,
            'jumlah' => $jumlahBiaya,
            'periode_mulai' => $penghuniKamar->tanggal_masuk,
            'periode_selesai' => $tanggalKeluar,
            'status' => 'pending',
            'bukti_transfer_url' => null,
            'tanggal_bayar' => null,
        ]);

        // Kirim notifikasi selamat datang & tagihan awal ke penghuni
        $kosNama = $kamar->kos->nama ?? 'Kos';
        \App\Models\Notifikasi::create([
            'user_id' => $penghuniKamar->penghuni_id,
            'judul' => 'Tagihan Pembayaran Awal Sewa Kos',
            'pesan' => "Selamat! Anda telah didaftarkan ke Kamar {$kamar->kode_kamar} ({$kosNama}). Silakan selesaikan pembayaran awal sewa sebesar Rp " . number_format($jumlahBiaya, 0, ',', '.'),
            'channel' => 'web',
            'status' => 'terkirim',
        ]);

        return $penghuniKamar;
    }

    public function update(int $id, array $data): PenghuniKamar
    {
        return $this->repository->update($id, $data);
    }

    public function checkout(int $id): PenghuniKamar
    {
        $record = $this->repository->updateStatus($id, 'selesai');

        // Cek apakah kamar jadi kosong
        $kamarId = $record->kamar_id;
        $jumlahAktif = $this->repository->getByKamar($kamarId)->where('status', 'aktif')->count();

        if ($jumlahAktif === 0) {
            $this->kamarRepository->updateStatus($kamarId, 'kosong');
        }

        return $record;
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
