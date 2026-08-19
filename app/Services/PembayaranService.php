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

    public function uploadBukti(int $id, string $buktiUrl, string $tipePerpanjangan = 'bulanan', int $jumlahHari = 30): Pembayaran
    {
        $pembayaran = Pembayaran::with('penghuniKamar.kamar')->findOrFail($id);
        $kamar = $pembayaran->penghuniKamar->kamar ?? null;

        // Cek apakah pembayaran ini adalah perpanjangan sewa (sudah ada pembayaran terverifikasi sebelumnya)
        $hasPreviousVerified = Pembayaran::where('penghuni_kamar_id', $pembayaran->penghuni_kamar_id)
            ->where('status', 'terverifikasi')
            ->where('id', '!=', $pembayaran->id)
            ->exists();

        $updateData = [
            'bukti_transfer_url' => $buktiUrl,
            'tanggal_bayar' => now(),
            'status' => 'pending',
        ];

        // Hanya perbarui skema biaya & periode jika ini adalah pembayaran perpanjangan sewa
        if ($hasPreviousVerified) {
            $jumlahBiaya = $pembayaran->jumlah;
            if ($kamar) {
                if ($tipePerpanjangan === 'harian') {
                    $hargaHarian = ($kamar->harga_per_hari ?? 0) > 0
                        ? $kamar->harga_per_hari
                        : round(($kamar->harga_per_bulan ?? 0) / 30);
                    $jumlahBiaya = $jumlahHari * $hargaHarian;
                } else {
                    $jumlahBiaya = $kamar->harga_per_bulan;
                    $jumlahHari = 30;
                }
            }

            $pk = $pembayaran->penghuniKamar;
            $baseDate = ($pk && $pk->tanggal_keluar)
                ? \Carbon\Carbon::parse($pk->tanggal_keluar)
                : \Carbon\Carbon::now();

            $updateData['tipe_perpanjangan'] = $tipePerpanjangan;
            $updateData['jumlah_hari'] = $jumlahHari;
            $updateData['jumlah'] = $jumlahBiaya;
            $updateData['periode_mulai'] = $baseDate->toDateString();
            $updateData['periode_selesai'] = $baseDate->copy()->addDays($jumlahHari)->toDateString();
        }

        $pembayaran->update($updateData);

        return $pembayaran->fresh();
    }

    public function verify(int $id, array $data): Pembayaran
    {
        $pembayaran = Pembayaran::with('penghuniKamar')->findOrFail($id);
        $data['tanggal_verifikasi'] = now();

        $pk = $pembayaran->penghuniKamar;
        if ($pk) {
            // Cek apakah pembayaran ini adalah pembayaran perpanjangan (bukan pembayaran awal pendaftaran)
            $hasPreviousVerified = Pembayaran::where('penghuni_kamar_id', $pembayaran->penghuni_kamar_id)
                ->where('status', 'terverifikasi')
                ->where('id', '!=', $pembayaran->id)
                ->exists();

            if (!$hasPreviousVerified) {
                // PEMBAYARAN AWAL PENDAFTARAN: JANGAN UBAH tanggal_keluar ATAU periode_mulai / periode_selesai!
                // Tanggal keluar di PenghuniKamar sudah diset secara akurat saat pendaftaran awal (misal: 19-25 Aug).
            } else {
                // PEMBAYARAN PERPANJANGAN SEWA: Tambahkan durasi baru ke tanggal_keluar penghuni
                $baseDate = $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->isFuture()
                    ? \Carbon\Carbon::parse($pk->tanggal_keluar)
                    : \Carbon\Carbon::now();

                $daysToAdd = $pembayaran->jumlah_hari ?: ($pembayaran->tipe_perpanjangan === 'harian' ? 1 : 30);
                $newTanggalKeluar = $baseDate->copy()->addDays($daysToAdd);

                $pk->update([
                    'tanggal_keluar' => $newTanggalKeluar->toDateString(),
                    'durasi' => $pembayaran->tipe_perpanjangan ?? $pk->durasi,
                ]);

                $data['periode_mulai'] = $baseDate->toDateString();
                $data['periode_selesai'] = $newTanggalKeluar->toDateString();
            }
        }

        $data['status'] = 'terverifikasi';

        $pembayaran->update($data);
        return $pembayaran->fresh();
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
            // Cek apakah sudah ada pembayaran status pending yang belum diselesaikan
            $pendingBilling = Pembayaran::where('penghuni_kamar_id', $penghuniKamar->id)
                ->where('status', 'pending')
                ->first();

            if (!$pendingBilling) {
                $kamar = $penghuniKamar->kamar;
                $hargaHarian = ($kamar->harga_per_hari ?? 0) > 0
                    ? $kamar->harga_per_hari
                    : round(($kamar->harga_per_bulan ?? 0) / 30);

                $jumlahBiaya = $penghuniKamar->durasi === 'harian'
                    ? $hargaHarian
                    : $kamar->harga_per_bulan;

                $newBilling = Pembayaran::create([
                    'penghuni_kamar_id' => $penghuniKamar->id,
                    'jumlah' => $jumlahBiaya,
                    'tipe_perpanjangan' => $penghuniKamar->durasi === 'harian' ? 'harian' : 'bulanan',
                    'jumlah_hari' => $penghuniKamar->durasi === 'harian' ? 1 : 30,
                    'periode_mulai' => $tanggalKeluar->toDateString(),
                    'periode_selesai' => $penghuniKamar->durasi === 'harian' ? $tanggalKeluar->copy()->addDay()->toDateString() : $tanggalKeluar->copy()->addDays(30)->toDateString(),
                    'status' => 'pending',
                ]);

                // Kirim notifikasi otomatis ke penghuni
                \App\Models\Notifikasi::create([
                    'user_id' => $penghuniKamar->penghuni_id,
                    'judul' => 'Tagihan Perpanjangan Sewa Kos',
                    'pesan' => "Masa tinggal Anda di Kamar " . ($kamar->kode_kamar ?? '-') . " tersisa {$sisaHari} hari. Tagihan perpanjangan sewa telah diterbitkan, silakan lakukan pembayaran.",
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
