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

    public function uploadBukti(int $id, string $buktiUrl, string $tipePerpanjangan = 'bulanan', int $jumlahHari = 30, int $porsiBayar = 100): Pembayaran
    {
        $pembayaran = Pembayaran::with('penghuniKamar.kamar')->findOrFail($id);
        $kamar = $pembayaran->penghuniKamar->kamar ?? null;

        $isBerbagi = ($kamar && $kamar->tipe === 'berbagi');
        if (!$isBerbagi) {
            $porsiBayar = 100;
        }

        // Cek apakah pembayaran ini adalah perpanjangan sewa (sudah ada pembayaran terverifikasi sebelumnya)
        $hasPreviousVerified = Pembayaran::where('penghuni_kamar_id', $pembayaran->penghuni_kamar_id)
            ->where('status', 'terverifikasi')
            ->where('id', '!=', $pembayaran->id)
            ->exists();

        $pk = $pembayaran->penghuniKamar;
        $isHarianPk = ($pembayaran->tipe_perpanjangan === 'harian') || ($pk && $pk->durasi === 'harian');
        $isMingguanPk = ($pembayaran->tipe_perpanjangan === 'mingguan') || ($pk && $pk->durasi === 'mingguan');

        if (!$hasPreviousVerified) {
            // PEMBAYARAN AWAL PENDAFTARAN:
            if ($isHarianPk) {
                $hargaHarian = ($kamar->harga_per_hari ?? 0) > 0
                    ? $kamar->harga_per_hari
                    : round(($kamar->harga_per_bulan ?? 0) / 30);
                $fullBiaya = ($pembayaran->jumlah_hari ?: 1) * $hargaHarian;
                $tipePerpanjangan = 'harian';
                $jumlahHari = $pembayaran->jumlah_hari ?: 1;
                $jumlahBiaya = ($isBerbagi && $porsiBayar == 50) ? round($fullBiaya / 2) : $fullBiaya;
            } elseif ($isMingguanPk) {
                $hargaMingguan = ($kamar->harga_per_minggu ?? 0) > 0
                    ? $kamar->harga_per_minggu
                    : round(($kamar->harga_per_bulan ?? 0) / 4);
                $fullBiaya = $hargaMingguan;
                $tipePerpanjangan = 'mingguan';
                $jumlahHari = 7;
                $jumlahBiaya = ($isBerbagi && $porsiBayar == 50) ? round($fullBiaya / 2) : $fullBiaya;
            } else {
                $fullBiaya = $kamar ? $kamar->harga_per_bulan : $pembayaran->jumlah;
                $jumlahBiaya = ($isBerbagi && $porsiBayar == 50) ? round($fullBiaya / 2) : $fullBiaya;
                $tipePerpanjangan = 'bulanan';
                $jumlahHari = 30;
            }
        } else {
            // PERPANJANGAN SEWA:
            if ($kamar) {
                if ($tipePerpanjangan === 'harian') {
                    $hargaHarian = ($kamar->harga_per_hari ?? 0) > 0
                        ? $kamar->harga_per_hari
                        : round(($kamar->harga_per_bulan ?? 0) / 30);
                    $fullBiaya = $jumlahHari * $hargaHarian;
                    $jumlahBiaya = ($isBerbagi && $porsiBayar == 50) ? round($fullBiaya / 2) : $fullBiaya;
                } elseif ($tipePerpanjangan === 'mingguan') {
                    $hargaMingguan = ($kamar->harga_per_minggu ?? 0) > 0
                        ? $kamar->harga_per_minggu
                        : round(($kamar->harga_per_bulan ?? 0) / 4);
                    $jumlahHari = 7;
                    $fullBiaya = $hargaMingguan;
                    $jumlahBiaya = ($isBerbagi && $porsiBayar == 50) ? round($fullBiaya / 2) : $fullBiaya;
                } else {
                    $fullBiaya = $kamar->harga_per_bulan;
                    $jumlahBiaya = ($isBerbagi && $porsiBayar == 50) ? round($fullBiaya / 2) : $fullBiaya;
                    $jumlahHari = 30;
                }
            }
        }

        $updateData = [
            'bukti_transfer_url' => $buktiUrl,
            'tanggal_bayar' => now(),
            'status' => 'pending',
            'porsi_bayar' => $porsiBayar,
            'jumlah' => $jumlahBiaya,
        ];

        // Hanya perbarui skema biaya & periode jika ini adalah pembayaran perpanjangan sewa
        if ($hasPreviousVerified) {
            $pk = $pembayaran->penghuniKamar;
            $baseDate = ($pk && $pk->tanggal_keluar)
                ? \Carbon\Carbon::parse($pk->tanggal_keluar)
                : \Carbon\Carbon::now();

            $updateData['tipe_perpanjangan'] = $tipePerpanjangan;
            $updateData['jumlah_hari'] = $jumlahHari;
            $updateData['periode_mulai'] = $baseDate->toDateString();
            $updateData['periode_selesai'] = $baseDate->copy()->addDays($jumlahHari)->toDateString();
        }

        $pembayaran->update($updateData);

        // Jika kamar berbagi:
        if ($isBerbagi && $pembayaran->penghuniKamar) {
            $roommatePk = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                ->where('status', 'aktif')
                ->where('id', '!=', $pembayaran->penghuni_kamar_id)
                ->first();

            if ($roommatePk) {
                $roommatePending = Pembayaran::where('penghuni_kamar_id', $roommatePk->id)
                    ->where('status', 'pending')
                    ->whereNull('bukti_transfer_url')
                    ->first();
                if ($roommatePending) {
                    $targetPorsi = $porsiBayar;
                    $targetAmount = ($porsiBayar == 100) ? $fullBiaya : round($fullBiaya / 2);
                    $roommatePending->update([
                        'jumlah' => $targetAmount,
                        'porsi_bayar' => $targetPorsi,
                    ]);
                }
            }
        }

        return $pembayaran->fresh();
    }

    public function verify(int $id, array $data): Pembayaran
    {
        $pembayaran = Pembayaran::with(['penghuniKamar.kamar', 'penghuniKamar.penghuni'])->findOrFail($id);
        $data['tanggal_verifikasi'] = now();

        $pk = $pembayaran->penghuniKamar;
        if ($pk) {
            $kamar = $pk->kamar;
            // Cek apakah pembayaran ini adalah pembayaran perpanjangan (bukan pembayaran awal pendaftaran)
            $hasPreviousVerified = Pembayaran::where('penghuni_kamar_id', $pembayaran->penghuni_kamar_id)
                ->where('status', 'terverifikasi')
                ->where('id', '!=', $pembayaran->id)
                ->exists();

            $daysToAdd = $pembayaran->jumlah_hari ?: ($pembayaran->tipe_perpanjangan === 'harian' ? 1 : ($pembayaran->tipe_perpanjangan === 'mingguan' ? 7 : 30));

            if (!$hasPreviousVerified) {
                // PEMBAYARAN AWAL PENDAFTARAN: JANGAN UBAH tanggal_keluar ATAU periode_mulai / periode_selesai!
            } else {
                // PEMBAYARAN PERPANJANGAN SEWA: Tambahkan durasi baru ke tanggal_keluar penghuni
                $baseDate = $pk->tanggal_keluar && \Carbon\Carbon::parse($pk->tanggal_keluar)->isFuture()
                    ? \Carbon\Carbon::parse($pk->tanggal_keluar)
                    : \Carbon\Carbon::now();

                $newTanggalKeluar = $baseDate->copy()->addDays($daysToAdd);

                $pk->update([
                    'tanggal_keluar' => $newTanggalKeluar->toDateString(),
                    'durasi' => $pembayaran->tipe_perpanjangan ?? $pk->durasi,
                ]);

                $data['periode_mulai'] = $baseDate->toDateString();
                $data['periode_selesai'] = $newTanggalKeluar->toDateString();
            }

            // JIKA KAMAR BERBAGI DAN DIBAYAR FULL (100%):
            // Otomatis verifikasi / lunaskan pembayaran teman sekamar untuk periode yang sama dengan nominal FULL 100%!
            if ($kamar && $kamar->tipe === 'berbagi' && $pembayaran->porsi_bayar == 100) {
                $uploaderName = $pk->penghuni->nama ?? 'Penghuni Kamar';
                $fullAmount = (float)$pembayaran->jumlah;
                if ($fullAmount <= 0) {
                    $fullAmount = (float)($kamar->harga_per_bulan ?? 0);
                }

                $roommatePk = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                    ->where('status', 'aktif')
                    ->where('id', '!=', $pk->id)
                    ->first();

                if ($roommatePk) {
                    $roommatePending = Pembayaran::where('penghuni_kamar_id', $roommatePk->id)
                        ->where('status', 'pending')
                        ->first();

                    $paymentDate = $pembayaran->tanggal_bayar ?? now();

                    if ($roommatePending) {
                        $roommatePending->update([
                            'jumlah' => $fullAmount,
                            'status' => 'terverifikasi',
                            'porsi_bayar' => 100,
                            'tanggal_bayar' => $paymentDate,
                            'tanggal_verifikasi' => now(),
                            'diverifikasi_oleh' => $data['diverifikasi_oleh'] ?? null,
                            'catatan_verifikasi' => "Lunas (Dibayar Tarif 2 Orang oleh {$uploaderName})",
                        ]);
                    } else {
                        Pembayaran::create([
                            'penghuni_kamar_id' => $roommatePk->id,
                            'jumlah' => $fullAmount,
                            'porsi_bayar' => 100,
                            'tipe_perpanjangan' => $pembayaran->tipe_perpanjangan,
                            'jumlah_hari' => $daysToAdd,
                            'periode_mulai' => $pembayaran->periode_mulai,
                            'periode_selesai' => $pembayaran->periode_selesai,
                            'status' => 'terverifikasi',
                            'tanggal_bayar' => $paymentDate,
                            'tanggal_verifikasi' => now(),
                            'diverifikasi_oleh' => $data['diverifikasi_oleh'] ?? null,
                            'catatan_verifikasi' => "Lunas (Dibayar Tarif 2 Orang oleh {$uploaderName})",
                        ]);
                    }

                    // Perbarui juga tanggal_keluar teman sekamar!
                    $roommatePk->update([
                        'tanggal_keluar' => $pk->fresh()->tanggal_keluar,
                        'durasi' => $pk->durasi,
                    ]);
                }
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
        if ($penghuniKamar->status !== 'aktif' || \Carbon\Carbon::parse($penghuniKamar->tanggal_masuk)->startOfDay()->gt(now()->startOfDay())) {
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
                $isBerbagi = ($kamar && $kamar->tipe === 'berbagi');
                $hargaMingguan = ($kamar->harga_per_minggu ?? 0) > 0 ? $kamar->harga_per_minggu : round(($kamar->harga_per_bulan ?? 0) / 4);
                $hargaHarian = ($kamar->harga_per_hari ?? 0) > 0 ? $kamar->harga_per_hari : round(($kamar->harga_per_bulan ?? 0) / 30);

                if ($isBerbagi && $penghuniKamar->durasi === 'bulanan') {
                    $jumlahBiaya = round(($kamar->harga_per_bulan ?? 0) / 2);
                    $defaultPorsi = 50;
                } elseif ($isBerbagi && $penghuniKamar->durasi === 'mingguan') {
                    $jumlahBiaya = round($hargaMingguan / 2);
                    $defaultPorsi = 50;
                } elseif ($isBerbagi && $penghuniKamar->durasi === 'harian') {
                    $jumlahBiaya = round($hargaHarian / 2);
                    $defaultPorsi = 50;
                } elseif ($penghuniKamar->durasi === 'mingguan') {
                    $jumlahBiaya = $hargaMingguan;
                    $defaultPorsi = 100;
                } elseif ($penghuniKamar->durasi === 'harian') {
                    $jumlahBiaya = $hargaHarian;
                    $defaultPorsi = 100;
                } else {
                    $jumlahBiaya = $kamar->harga_per_bulan ?? 0;
                    $defaultPorsi = 100;
                }

                $daysForBilling = $penghuniKamar->durasi === 'harian' ? 1 : ($penghuniKamar->durasi === 'mingguan' ? 7 : 30);

                $newBilling = Pembayaran::create([
                    'penghuni_kamar_id' => $penghuniKamar->id,
                    'jumlah' => $jumlahBiaya,
                    'porsi_bayar' => $defaultPorsi,
                    'tipe_perpanjangan' => $penghuniKamar->durasi ?? 'bulanan',
                    'jumlah_hari' => $daysForBilling,
                    'periode_mulai' => $tanggalKeluar->toDateString(),
                    'periode_selesai' => $tanggalKeluar->copy()->addDays($daysForBilling)->toDateString(),
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
