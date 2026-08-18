<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Repositories\Contracts\PembayaranRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PembayaranService
{
    public function __construct(
        protected PembayaranRepositoryInterface $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function getByPenghuniKamar(int $penghuniKamarId): Collection
    {
        return $this->repository->getByPenghuniKamar($penghuniKamarId);
    }

    public function getPending(): Collection
    {
        return $this->repository->getPending();
    }

    public function getTerverifikasi(): Collection
    {
        return $this->repository->getTerverifikasi();
    }

    public function getDitolak(): Collection
    {
        return $this->repository->getDitolak();
    }

    public function getById(int $id): ?Pembayaran
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): Pembayaran
    {
        return $this->repository->create($data);
    }

    public function uploadBukti(int $id, string $buktiUrl): Pembayaran
    {
        return $this->repository->update($id, [
            'bukti_transfer_url' => $buktiUrl,
            'tanggal_bayar' => now(),
            'status' => 'pending',
        ]);
    }

    public function verify(int $id, array $data): Pembayaran
    {
        $data['tanggal_verifikasi'] = now();
        return $this->repository->verify($id, $data);
    }

    public function reject(int $id, string $catatan, int $adminId): Pembayaran
    {
        $oldPembayaran = Pembayaran::findOrFail($id);

        // 1. Update status pembayaran lama menjadi 'ditolak' (tetap tersimpan di log/riwayat)
        $oldPembayaran->update([
            'status' => 'ditolak',
            'catatan_verifikasi' => $catatan,
            'diverifikasi_oleh' => $adminId,
            'tanggal_verifikasi' => now(),
        ]);

        // 2. Buat pembayaran/tagihan baru secara otomatis untuk penghuni agar bisa kirim form bukti transfer baru
        Pembayaran::create([
            'penghuni_kamar_id' => $oldPembayaran->penghuni_kamar_id,
            'jumlah' => $oldPembayaran->jumlah,
            'periode_mulai' => $oldPembayaran->periode_mulai,
            'periode_selesai' => $oldPembayaran->periode_selesai,
            'status' => 'pending',
            'bukti_transfer_url' => null,
            'tanggal_bayar' => null,
        ]);

        // 3. Kirim notifikasi ke penghuni mengenai penolakan dan form pembayaran baru
        $pk = $oldPembayaran->penghuniKamar;
        if ($pk) {
            \App\Models\Notifikasi::create([
                'user_id' => $pk->penghuni_id,
                'judul' => 'Pembayaran Ditolak - Upload Ulang',
                'pesan' => "Pembayaran Anda ditolak oleh Admin dengan catatan: '{$catatan}'. Silakan lakukan pengunggahan ulang bukti pembayaran.",
                'channel' => 'web',
                'status' => 'terkirim',
            ]);
        }

        return $oldPembayaran;
    }

    public function checkAndGenerateAutoBilling(\App\Models\PenghuniKamar $penghuniKamar): ?Pembayaran
    {
        if ($penghuniKamar->status !== 'aktif') {
            return null;
        }

        $tanggalKeluar = $penghuniKamar->tanggal_keluar
            ? \Carbon\Carbon::parse($penghuniKamar->tanggal_keluar)
            : \Carbon\Carbon::parse($penghuniKamar->tanggal_masuk)->addMonth();

        $today = \Carbon\Carbon::now()->startOfDay();
        $sisaHari = (int) $today->diffInDays($tanggalKeluar->startOfDay(), false);

        // Jika sisa hari <= 7 hari
        if ($sisaHari <= 7) {
            // Cek apakah sudah ada pembayaran pending atau terverifikasi untuk siklus ini
            $existingBilling = Pembayaran::where('penghuni_kamar_id', $penghuniKamar->id)
                ->whereIn('status', ['pending', 'terverifikasi'])
                ->first();

            if (!$existingBilling) {
                $kamar = $penghuniKamar->kamar;
                $jumlahBiaya = $penghuniKamar->durasi === 'harian'
                    ? ($kamar->harga_per_hari ?? 0)
                    : $kamar->harga_per_bulan;

                $newBilling = Pembayaran::create([
                    'penghuni_kamar_id' => $penghuniKamar->id,
                    'jumlah' => $jumlahBiaya,
                    'periode_mulai' => $penghuniKamar->tanggal_masuk,
                    'periode_selesai' => $tanggalKeluar,
                    'status' => 'pending',
                ]);

                // Kirim notifikasi otomatis ke penghuni
                \App\Models\Notifikasi::create([
                    'user_id' => $penghuniKamar->penghuni_id,
                    'judul' => 'Tagihan Pembayaran Sewa Kos',
                    'pesan' => "Masa tinggal Anda di Kamar " . ($kamar->kode_kamar ?? '-') . " tersisa {$sisaHari} hari. Tagihan sewa telah diterbitkan, silakan lakukan pembayaran.",
                    'channel' => 'web',
                    'status' => 'terkirim',
                ]);

                return $newBilling;
            }
        }

        return null;
    }

    public function getLaporan(string $start, string $end): Collection
    {
        return $this->repository->getLaporanByDateRange($start, $end);
    }

    public function getByKos(int $kosId): Collection
    {
        return $this->repository->getByKos($kosId);
    }
}
