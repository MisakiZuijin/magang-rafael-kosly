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

    public function syncKapasitasDanStatus(int $kamarId): void
    {
        $kamar = Kamar::find($kamarId);
        if (!$kamar) return;

        $jumlahAktif = PenghuniKamar::where('kamar_id', $kamarId)
            ->where('status', 'aktif')
            ->count();

        if ($kamar->tipe === 'berbagi') {
            if ($jumlahAktif >= 3) {
                if ((int)$kamar->kapasitas !== 3) {
                    $kamar->update(['kapasitas' => 3]);
                }
            } elseif ($jumlahAktif === 0) {
                if ((int)$kamar->kapasitas !== 2) {
                    $kamar->update(['kapasitas' => 2]);
                }
            }
        }

        if ($jumlahAktif >= $kamar->kapasitas) {
            $this->kamarRepository->updateStatus($kamarId, 'terisi');
        } elseif ($jumlahAktif === 0) {
            $this->kamarRepository->updateStatus($kamarId, 'kosong');
        }
    }

    public function create(array $data): PenghuniKamar
    {
        $penghuniKamar = $this->repository->create($data);

        // Sync kapasitas dan status kamar
        $this->syncKapasitasDanStatus($data['kamar_id']);

        $kamar = $this->kamarRepository->findById($data['kamar_id']);

        // --- BUAT TAGIHAN PEMBAYARAN AWAL AUTOMATIS ---
        $tanggalMasukObj = \Carbon\Carbon::parse($penghuniKamar->tanggal_masuk)->startOfDay();

        if ($penghuniKamar->tanggal_keluar) {
            $tanggalKeluarObj = \Carbon\Carbon::parse($penghuniKamar->tanggal_keluar)->startOfDay();
        } else {
            if ($penghuniKamar->durasi === 'harian') {
                $tanggalKeluarObj = $tanggalMasukObj->copy()->addDay();
            } elseif ($penghuniKamar->durasi === 'mingguan') {
                $tanggalKeluarObj = $tanggalMasukObj->copy()->addDays(7);
            } else {
                $tanggalKeluarObj = $tanggalMasukObj->copy()->addDays(30);
            }

            $penghuniKamar->update(['tanggal_keluar' => $tanggalKeluarObj->toDateString()]);
        }

        $selisihHari = max(1, (int) $tanggalMasukObj->diffInDays($tanggalKeluarObj));
        $activePenghuniCount = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
            ->where('status', 'aktif')
            ->count();
        if ($activePenghuniCount < 1) $activePenghuniCount = 1;

        $defaultPorsi = 100;
        if ($penghuniKamar->durasi === 'harian') {
            $hargaHarian = ($kamar->harga_per_hari ?? 0) > 0
                ? $kamar->harga_per_hari
                : round(($kamar->harga_per_bulan ?? 0) / 30);
            $totalDailyRoom = $selisihHari * $hargaHarian;
            if ($kamar->tipe === 'berbagi' && $activePenghuniCount <= 2) {
                $jumlahBiaya = round($totalDailyRoom / 2);
                $defaultPorsi = 50;
            } else {
                $jumlahBiaya = $totalDailyRoom;
                $defaultPorsi = 100;
            }
            $jumlahHari = $selisihHari;
            $nominalNotif = $totalDailyRoom;
            $tipePerpanjangan = 'harian';
        } elseif ($penghuniKamar->durasi === 'mingguan') {
            $jumlahMinggu = max(1, (int) round($selisihHari / 7));
            if ($selisihHari <= 7) {
                $jumlahMinggu = 1;
            }
            $hargaMingguan = ($kamar->harga_per_minggu ?? 0) > 0
                ? $kamar->harga_per_minggu
                : round(($kamar->harga_per_bulan ?? 0) / 4);
            $totalWeeklyRoom = $jumlahMinggu * $hargaMingguan;
            if ($kamar->tipe === 'berbagi' && $activePenghuniCount <= 2) {
                $jumlahBiaya = round($totalWeeklyRoom / 2);
                $defaultPorsi = 50;
            } else {
                $jumlahBiaya = $totalWeeklyRoom;
                $defaultPorsi = 100;
            }
            $jumlahHari = $selisihHari > 0 ? $selisihHari : 7;
            $nominalNotif = $totalWeeklyRoom;
            $tipePerpanjangan = 'mingguan';
        } else {
            $fullRoomMonth = $kamar->harga_per_bulan ?? 0;
            if ($kamar->tipe === 'berbagi' && $activePenghuniCount <= 2) {
                $jumlahBiaya = round($fullRoomMonth / 2);
                $defaultPorsi = 50;
            } else {
                $jumlahBiaya = $fullRoomMonth;
                $defaultPorsi = 100;
            }
            $jumlahHari = $selisihHari > 0 ? $selisihHari : 30;
            $nominalNotif = $kamar->harga_per_bulan ?? $jumlahBiaya;
            $tipePerpanjangan = 'bulanan';
        }

        \App\Models\Pembayaran::create([
            'penghuni_kamar_id' => $penghuniKamar->id,
            'jumlah' => $jumlahBiaya,
            'porsi_bayar' => $defaultPorsi,
            'tipe_perpanjangan' => $tipePerpanjangan,
            'jumlah_hari' => $jumlahHari,
            'periode_mulai' => $penghuniKamar->tanggal_masuk,
            'periode_selesai' => $tanggalKeluarObj->toDateString(),
            'status' => 'pending',
            'bukti_transfer_url' => null,
            'tanggal_bayar' => null,
        ]);

        // Kirim notifikasi selamat datang & tagihan awal ke penghuni
        $kosNama = $kamar->kos->nama ?? 'Kos';
        \App\Models\Notifikasi::create([
            'user_id' => $penghuniKamar->penghuni_id,
            'judul' => 'Tagihan Pembayaran Awal Sewa Kos',
            'pesan' => "Selamat! Anda telah didaftarkan ke Kamar {$kamar->kode_kamar} ({$kosNama}). Silakan selesaikan pembayaran awal sewa sebesar Rp " . number_format($nominalNotif, 0, ',', '.'),
            'channel' => 'web',
            'status' => 'terkirim',
        ]);

        return $penghuniKamar;
    }

    public function update(int $id, array $data): PenghuniKamar
    {
        $penghuniKamar = $this->repository->update($id, $data);
        if (isset($data['kamar_id'])) {
            $this->syncKapasitasDanStatus($data['kamar_id']);
        }
        return $penghuniKamar;
    }

    public function checkout(int $id): PenghuniKamar
    {
        $record = $this->repository->updateStatus($id, 'selesai');

        // Sync kapasitas dan status kamar
        $this->syncKapasitasDanStatus($record->kamar_id);

        return $record;
    }

    public function delete(int $id): bool
    {
        $record = $this->repository->findById($id);
        $kamarId = $record ? $record->kamar_id : null;

        $result = $this->repository->delete($id);

        if ($kamarId) {
            $this->syncKapasitasDanStatus($kamarId);
        }

        return $result;
    }
}
