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
        $pembayaran = Pembayaran::with(['penghuniKamar.kamar', 'penghuniKamar.penghuni'])->findOrFail($id);
        $kamar = $pembayaran->penghuniKamar->kamar ?? null;

        $isBerbagi = ($kamar && $kamar->tipe === 'berbagi');
        $activePenghuniCount = $kamar
            ? \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)->where('status', 'aktif')->count()
            : 1;
        if ($activePenghuniCount < 1) $activePenghuniCount = 1;

        // Cek jika teman sekamar sudah membayar setengah (50%) pada tagihan pending saat ini:
        if ($isBerbagi && $activePenghuniCount <= 2) {
            $currentPeriodeMulai = $pembayaran->periode_mulai;
            $roommateHas50 = Pembayaran::whereHas('penghuniKamar', function ($q) use ($kamar, $pembayaran) {
                $q->where('kamar_id', $kamar->id)
                  ->where('status', 'aktif')
                  ->where('id', '!=', $pembayaran->penghuni_kamar_id);
            })
            ->where('status', 'pending')
            ->whereNotNull('bukti_transfer_url')
            ->where('porsi_bayar', 50)
            ->where(function($q) use ($currentPeriodeMulai) {
                if ($currentPeriodeMulai) {
                    $q->where('periode_mulai', $currentPeriodeMulai);
                }
            })
            ->exists();

            if ($roommateHas50) {
                $porsiBayar = 50;
            }
        } elseif (!$isBerbagi || $activePenghuniCount >= 3) {
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
                $jumlahBiaya = ($isBerbagi && $porsiBayar == 50 && $activePenghuniCount <= 2) ? round($fullBiaya / 2) : $fullBiaya;
            } elseif ($isMingguanPk) {
                $hargaMingguan = ($kamar->harga_per_minggu ?? 0) > 0
                    ? $kamar->harga_per_minggu
                    : round(($kamar->harga_per_bulan ?? 0) / 4);
                $fullBiaya = $hargaMingguan;
                $tipePerpanjangan = 'mingguan';
                $jumlahHari = 7;
                $jumlahBiaya = ($isBerbagi && $porsiBayar == 50 && $activePenghuniCount <= 2) ? round($fullBiaya / 2) : $fullBiaya;
            } else {
                $fullBiaya = $kamar ? $kamar->harga_per_bulan : $pembayaran->jumlah;
                $jumlahBiaya = ($isBerbagi && $porsiBayar == 50 && $activePenghuniCount <= 2) ? round($fullBiaya / 2) : $fullBiaya;
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
                    $jumlahBiaya = ($isBerbagi && $porsiBayar == 50 && $activePenghuniCount <= 2) ? round($fullBiaya / 2) : $fullBiaya;
                } elseif ($tipePerpanjangan === 'mingguan') {
                    $hargaMingguan = ($kamar->harga_per_minggu ?? 0) > 0
                        ? $kamar->harga_per_minggu
                        : round(($kamar->harga_per_bulan ?? 0) / 4);
                    $jumlahHari = 7;
                    $fullBiaya = $hargaMingguan;
                    $jumlahBiaya = ($isBerbagi && $porsiBayar == 50 && $activePenghuniCount <= 2) ? round($fullBiaya / 2) : $fullBiaya;
                } else {
                    $fullBiaya = $kamar->harga_per_bulan;
                    $jumlahBiaya = ($isBerbagi && $porsiBayar == 50 && $activePenghuniCount <= 2) ? round($fullBiaya / 2) : $fullBiaya;
                    $jumlahHari = 30;
                }
            }
        }

        $uploaderUser = \Illuminate\Support\Facades\Auth::user() ?? ($pembayaran->penghuniKamar->penghuni ?? null);
        $uploaderName = $uploaderUser->nama ?? 'Penghuni Kamar';
        $uploadTimeStr = now()->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';

        $activeRoomPenghuniCount = $isBerbagi
            ? \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)->where('status', 'aktif')->count()
            : 1;
        $tarifLabel = ($isBerbagi && $activeRoomPenghuniCount >= 3)
            ? 'Tarif 3 Orang'
            : ($porsiBayar == 50 ? 'Tarif 1 Orang' : ($isBerbagi ? 'Tarif 2 Orang' : 'Tarif Standar'));

        $updateData = [
            'bukti_transfer_url' => $buktiUrl,
            'tanggal_bayar' => now(),
            'status' => 'pending',
            'porsi_bayar' => $porsiBayar,
            'jumlah' => $jumlahBiaya,
            'catatan_verifikasi' => "Menunggu verifikasi admin ({$tarifLabel} diunggah oleh {$uploaderName} pada {$uploadTimeStr})",
        ];

        // Hanya perbarui skema biaya & periode jika ini adalah pembayaran perpanjangan sewa
        if ($hasPreviousVerified) {
            $pk = $pembayaran->penghuniKamar;
            $baseDate = ($pk && $pk->tanggal_keluar)
                ? \Carbon\Carbon::parse($pk->tanggal_keluar)
                : \Carbon\Carbon::now();

            if ($tipePerpanjangan === 'bulanan') {
                $periodeSelesai = $baseDate->copy()->addDays(30)->toDateString();
            } elseif ($tipePerpanjangan === 'mingguan') {
                $periodeSelesai = $baseDate->copy()->addDays(7)->toDateString();
            } else {
                $periodeSelesai = $baseDate->copy()->addDays($jumlahHari)->toDateString();
            }

            $updateData['tipe_perpanjangan'] = $tipePerpanjangan;
            $updateData['jumlah_hari'] = $jumlahHari;
            $updateData['periode_mulai'] = $baseDate->toDateString();
            $updateData['periode_selesai'] = $periodeSelesai;
        }

        $pembayaran->update($updateData);

        // Jika kamar berbagi:
        if ($isBerbagi && $pembayaran->penghuniKamar) {
            $roommatePks = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                ->where('status', 'aktif')
                ->where('id', '!=', $pembayaran->penghuni_kamar_id)
                ->get();

            foreach ($roommatePks as $roommatePk) {
                if ($porsiBayar == 100) {
                    // KASUS 1: BAYAR FULL (100%) -> Teman sekamar disinkronkan porsi 100%, bukti_transfer_url tetap NULL agar hanya 1 transaksi verifikasi yang masuk ke Admin
                    $roommatePending = Pembayaran::where('penghuni_kamar_id', $roommatePk->id)
                        ->where('status', 'pending')
                        ->first();

                    $syncData = [
                        'bukti_transfer_url' => null,
                        'tanggal_bayar' => now(),
                        'status' => 'pending',
                        'porsi_bayar' => 100,
                        'jumlah' => $jumlahBiaya,
                        'catatan_verifikasi' => "Menunggu verifikasi admin (Pelunasan 1 kamar {$tarifLabel} diunggah oleh {$uploaderName} pada {$uploadTimeStr})",
                    ];

                    if ($hasPreviousVerified) {
                        $syncData['tipe_perpanjangan'] = $tipePerpanjangan;
                        $syncData['jumlah_hari'] = $jumlahHari;
                        $syncData['periode_mulai'] = $updateData['periode_mulai'] ?? null;
                        $syncData['periode_selesai'] = $updateData['periode_selesai'] ?? null;
                    }

                    if ($roommatePending) {
                        $roommatePending->update($syncData);
                    } else {
                        $syncData['penghuni_kamar_id'] = $roommatePk->id;
                        $syncData['periode_mulai'] = $pembayaran->periode_mulai;
                        $syncData['periode_selesai'] = $pembayaran->periode_selesai;
                        $syncData['tipe_perpanjangan'] = $pembayaran->tipe_perpanjangan ?? 'bulanan';
                        $syncData['jumlah_hari'] = $pembayaran->jumlah_hari ?? 30;
                        Pembayaran::create($syncData);
                    }
                } else {
                    // KASUS 2: BAYAR SETENGAH (50%) -> Tagihan teman sekamar disesuaikan menjadi 50% dengan durasi dan jumlah hari yang sama persis
                    $roommatePending = Pembayaran::where('penghuni_kamar_id', $roommatePk->id)
                        ->where('status', 'pending')
                        ->whereNull('bukti_transfer_url')
                        ->first();
                    if ($roommatePending) {
                        $targetAmount = round($fullBiaya / 2);
                        $syncData50 = [
                            'jumlah' => $targetAmount,
                            'porsi_bayar' => 50,
                            'tipe_perpanjangan' => $tipePerpanjangan,
                            'jumlah_hari' => $jumlahHari,
                        ];

                        if ($hasPreviousVerified) {
                            $syncData50['periode_mulai'] = $updateData['periode_mulai'] ?? null;
                            $syncData50['periode_selesai'] = $updateData['periode_selesai'] ?? null;
                        }

                        $roommatePending->update($syncData50);
                    }
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
                // PEMBAYARAN PERPANJANGAN SEWA: Tambahkan durasi baru dari hari akhir masa sewa (tanggal_keluar sebelumnya)
                $baseDate = ($pk && $pk->tanggal_keluar)
                    ? \Carbon\Carbon::parse($pk->tanggal_keluar)
                    : \Carbon\Carbon::now();

                if ($pembayaran->tipe_perpanjangan === 'bulanan') {
                    $newTanggalKeluar = $baseDate->copy()->addDays(30);
                } elseif ($pembayaran->tipe_perpanjangan === 'mingguan') {
                    $newTanggalKeluar = $baseDate->copy()->addDays(7);
                } else {
                    $newTanggalKeluar = $baseDate->copy()->addDays($daysToAdd);
                }

                $newTanggalKeluar = $newTanggalKeluar->setTime(14, 0, 0);

                $pk->update([
                    'tanggal_keluar' => $newTanggalKeluar->toDateTimeString(),
                    'durasi' => $pembayaran->tipe_perpanjangan ?? $pk->durasi,
                    'notif_jatuh_tempo_sent_at' => null,
                ]);

                $data['periode_mulai'] = $baseDate->toDateString();
                $data['periode_selesai'] = $newTanggalKeluar->toDateString();
            }

            // JIKA KAMAR BERBAGI DAN DIBAYAR FULL (100%):
            // Otomatis verifikasi / lunaskan pembayaran seluruh teman sekamar untuk periode yang sama dengan nominal FULL 100%!
            if ($kamar && $kamar->tipe === 'berbagi' && $pembayaran->porsi_bayar == 100) {
                $uploaderName = $pk->penghuni->nama ?? 'Penghuni Kamar';
                $fullAmount = (float)$pembayaran->jumlah;
                if ($fullAmount <= 0) {
                    $fullAmount = (float)($kamar->harga_per_bulan ?? 0);
                }

                $roommatePks = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                    ->where('status', 'aktif')
                    ->where('id', '!=', $pk->id)
                    ->get();

                $totalOccupants = $roommatePks->count() + 1;
                $labelTarif = ($totalOccupants >= 3 || str_contains($pembayaran->catatan_verifikasi ?? '', '3 Orang')) ? 'Tarif 3 Orang' : 'Tarif 2 Orang';

                foreach ($roommatePks as $roommatePk) {
                    $paymentDate = $pembayaran->tanggal_bayar ?? now();

                    // 1. Cek apakah teman sekamar SUDAH memiliki pembayaran terverifikasi untuk periode ini (cegah duplikat!)
                    $existingVerified = Pembayaran::where('penghuni_kamar_id', $roommatePk->id)
                        ->where('status', 'terverifikasi')
                        ->where('periode_mulai', $pembayaran->periode_mulai)
                        ->first();

                    if ($existingVerified) {
                        $existingVerified->update([
                            'jumlah' => $fullAmount,
                            'porsi_bayar' => 100,
                            'bukti_transfer_url' => $pembayaran->bukti_transfer_url,
                            'tanggal_bayar' => $paymentDate,
                            'tanggal_verifikasi' => now(),
                            'diverifikasi_oleh' => $data['diverifikasi_oleh'] ?? null,
                            'catatan_verifikasi' => "Lunas (Dibayar {$labelTarif} oleh {$uploaderName})",
                        ]);

                        // Hapus jika ada duplikat berlebih untuk periode yang sama
                        Pembayaran::where('penghuni_kamar_id', $roommatePk->id)
                            ->where('status', 'terverifikasi')
                            ->where('periode_mulai', $pembayaran->periode_mulai)
                            ->where('id', '!=', $existingVerified->id)
                            ->delete();
                    } else {
                        $roommatePending = Pembayaran::where('penghuni_kamar_id', $roommatePk->id)
                            ->where('status', 'pending')
                            ->first();

                        if ($roommatePending) {
                            $roommatePending->update([
                                'jumlah' => $fullAmount,
                                'status' => 'terverifikasi',
                                'porsi_bayar' => 100,
                                'bukti_transfer_url' => $pembayaran->bukti_transfer_url,
                                'tanggal_bayar' => $paymentDate,
                                'tanggal_verifikasi' => now(),
                                'diverifikasi_oleh' => $data['diverifikasi_oleh'] ?? null,
                                'catatan_verifikasi' => "Lunas (Dibayar {$labelTarif} oleh {$uploaderName})",
                            ]);
                        } else {
                            Pembayaran::create([
                                'penghuni_kamar_id' => $roommatePk->id,
                                'jumlah' => $fullAmount,
                                'porsi_bayar' => 100,
                                'bukti_transfer_url' => $pembayaran->bukti_transfer_url,
                                'tipe_perpanjangan' => $pembayaran->tipe_perpanjangan,
                                'jumlah_hari' => $daysToAdd,
                                'periode_mulai' => $pembayaran->periode_mulai,
                                'periode_selesai' => $pembayaran->periode_selesai,
                                'status' => 'terverifikasi',
                                'tanggal_bayar' => $paymentDate,
                                'tanggal_verifikasi' => now(),
                                'diverifikasi_oleh' => $data['diverifikasi_oleh'] ?? null,
                                'catatan_verifikasi' => "Lunas (Dibayar {$labelTarif} oleh {$uploaderName})",
                            ]);
                        }
                    }

                    // Perbarui juga tanggal_keluar teman sekamar jika perpanjangan sewa!
                    if ($hasPreviousVerified) {
                        $roommatePk->update([
                            'tanggal_keluar' => $pk->fresh()->tanggal_keluar,
                            'durasi' => $pk->durasi,
                            'notif_jatuh_tempo_sent_at' => null,
                        ]);
                    }
                }
            }
        }

        $verifTimeStr = now()->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';

        if ($kamar && $kamar->tipe === 'berbagi') {
            $roommateCount = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                ->where('status', 'aktif')
                ->where('id', '!=', $pk->id)
                ->count();
            $totalOccupants = $roommateCount + 1;
            $labelTarif = ($totalOccupants >= 3 || str_contains($pembayaran->catatan_verifikasi ?? '', '3 Orang')) ? 'Tarif 3 Orang' : 'Tarif 2 Orang';

            if ($pembayaran->porsi_bayar == 100) {
                $data['catatan_verifikasi'] = "Lunas (Pelunasan 1 Kamar {$labelTarif} terverifikasi pada {$verifTimeStr})";
            } else {
                $data['catatan_verifikasi'] = "Lunas (Pembayaran Tarif 1 Orang terverifikasi pada {$verifTimeStr})";
            }
        } else {
            $data['catatan_verifikasi'] = "Lunas (Pembayaran sewa terverifikasi pada {$verifTimeStr})";
        }

        $data['status'] = 'terverifikasi';

        $pembayaran->update($data);
        return $pembayaran->fresh();
    }

    public function reject(int $id, string $catatan, int $adminId): Pembayaran
    {
        $oldPembayaran = Pembayaran::with(['penghuniKamar.kamar', 'penghuniKamar.penghuni'])->findOrFail($id);
        $kamar = $oldPembayaran->penghuniKamar->kamar ?? null;
        $isBerbagi = ($kamar && $kamar->tipe === 'berbagi');

        // 1. Update status pembayaran lama menjadi 'ditolak' (tetap tersimpan di log/riwayat)
        $oldPembayaran->update([
            'status' => 'ditolak',
            'catatan_verifikasi' => $catatan,
            'diverifikasi_oleh' => $adminId,
            'tanggal_verifikasi' => now(),
        ]);

        $defaultMulai = $oldPembayaran->periode_mulai ?? now()->toDateString();
        $defaultSelesai = $oldPembayaran->periode_selesai ?? now()->addDays(30)->toDateString();

        // 2. Buat pembayaran/tagihan baru secara otomatis untuk penghuni agar bisa kirim form bukti transfer baru
        Pembayaran::create([
            'penghuni_kamar_id' => $oldPembayaran->penghuni_kamar_id,
            'jumlah' => $oldPembayaran->jumlah,
            'porsi_bayar' => $oldPembayaran->porsi_bayar,
            'tipe_perpanjangan' => $oldPembayaran->tipe_perpanjangan ?? 'bulanan',
            'jumlah_hari' => $oldPembayaran->jumlah_hari ?? 30,
            'periode_mulai' => $defaultMulai,
            'periode_selesai' => $defaultSelesai,
            'status' => 'pending',
            'bukti_transfer_url' => null,
            'tanggal_bayar' => null,
        ]);

        // Jika kamar berbagi dan pembayaran yang ditolak adalah 100% full:
        if ($isBerbagi && $oldPembayaran->porsi_bayar == 100) {
            $roommatePks = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                ->where('status', 'aktif')
                ->where('id', '!=', $oldPembayaran->penghuni_kamar_id)
                ->get();

            foreach ($roommatePks as $rPk) {
                $rPending = Pembayaran::where('penghuni_kamar_id', $rPk->id)
                    ->where('status', 'pending')
                    ->where('bukti_transfer_url', $oldPembayaran->bukti_transfer_url)
                    ->first();

                if ($rPending) {
                    $rPending->update([
                        'status' => 'ditolak',
                        'catatan_verifikasi' => "Pembayaran 1 kamar ditolak oleh Admin: {$catatan}",
                        'diverifikasi_oleh' => $adminId,
                        'tanggal_verifikasi' => now(),
                    ]);

                    Pembayaran::create([
                        'penghuni_kamar_id' => $rPk->id,
                        'jumlah' => $oldPembayaran->jumlah,
                        'porsi_bayar' => 100,
                        'tipe_perpanjangan' => $oldPembayaran->tipe_perpanjangan ?? 'bulanan',
                        'jumlah_hari' => $oldPembayaran->jumlah_hari ?? 30,
                        'periode_mulai' => $defaultMulai,
                        'periode_selesai' => $defaultSelesai,
                        'status' => 'pending',
                        'bukti_transfer_url' => null,
                        'tanggal_bayar' => null,
                    ]);
                }
            }
        }

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

        // 1. Cek apakah penghuni ini sendiri SUDAH memiliki pembayaran awal yang terverifikasi
        $hasVerifiedInitial = Pembayaran::where('penghuni_kamar_id', $penghuniKamar->id)
            ->where('status', 'terverifikasi')
            ->exists();

        if (!$hasVerifiedInitial) {
            // Belum bayar biaya awal, jangan pernah generate tagihan perpanjangan!
            return null;
        }

        $kamar = $penghuniKamar->kamar;
        $isBerbagi = ($kamar && $kamar->tipe === 'berbagi');
        $activePenghuniCount = $kamar
            ? \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)->where('status', 'aktif')->count()
            : 1;

        // 2. Jika kamar berbagi (2 atau 3 orang):
        // Cek apakah seluruh teman sekamar aktif SUDAH menyelesaikan pembayaran awal!
        // Jika rekan sekamar belum bayar biaya awal, kamar belum aktif penuh dan jangan generate perpanjangan dulu!
        if ($isBerbagi) {
            $roommatePks = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                ->where('status', 'aktif')
                ->where('id', '!=', $penghuniKamar->id)
                ->get();

            foreach ($roommatePks as $rPk) {
                $rHasVerified = Pembayaran::where('penghuni_kamar_id', $rPk->id)
                    ->where('status', 'terverifikasi')
                    ->exists();

                if (!$rHasVerified) {
                    return null;
                }
            }
        }

        $tanggalKeluar = $penghuniKamar->tanggal_keluar
            ? \Carbon\Carbon::parse($penghuniKamar->tanggal_keluar)
            : \Carbon\Carbon::parse($penghuniKamar->tanggal_masuk)->addMonth();

        $today = \Carbon\Carbon::now()->startOfDay();
        $sisaHari = (int) $today->diffInDays($tanggalKeluar->startOfDay(), false);

        // 3. Batas hari pemicu perpanjangan otomatis:
        // Jika masa sewa sisa <= 7 hari (atau mendekati batas keluar untuk harian/mingguan), terbitkan tagihan perpanjangan
        $triggerDays = 7;

        // Jika sisa hari <= batas hari pemicu
        if ($sisaHari <= $triggerDays) {
            // Cek apakah sudah ada pembayaran status pending yang belum diselesaikan
            $pendingBilling = Pembayaran::where('penghuni_kamar_id', $penghuniKamar->id)
                ->where('status', 'pending')
                ->first();

            if (!$pendingBilling) {
                $kamar = $penghuniKamar->kamar;
                $isBerbagi = ($kamar && $kamar->tipe === 'berbagi');
                $hargaMingguan = ($kamar->harga_per_minggu ?? 0) > 0 ? $kamar->harga_per_minggu : round(($kamar->harga_per_bulan ?? 0) / 4);
                $hargaHarian = ($kamar->harga_per_hari ?? 0) > 0 ? $kamar->harga_per_hari : round(($kamar->harga_per_bulan ?? 0) / 30);

                $activePenghuniCount = $kamar
                    ? \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)->where('status', 'aktif')->count()
                    : 1;
                if ($activePenghuniCount < 1) $activePenghuniCount = 1;

                if ($isBerbagi && $activePenghuniCount >= 3) {
                    // Kamar berbagi 3 orang: Tagihan perpanjangan adalah 1 KAMAR FULL (100%), dibayar jadi 1 seperti pembayaran awal 3 orang
                    if ($penghuniKamar->durasi === 'harian') {
                        $jumlahBiaya = $hargaHarian;
                    } elseif ($penghuniKamar->durasi === 'mingguan') {
                        $jumlahBiaya = $hargaMingguan;
                    } else {
                        $jumlahBiaya = $kamar->harga_per_bulan ?? 0;
                    }
                    $defaultPorsi = 100;
                } elseif ($isBerbagi && $penghuniKamar->durasi === 'bulanan') {
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

                // Jika kamar berbagi, pastikan seluruh teman sekamar aktif juga mendapatkan tagihan perpanjangan
                if ($isBerbagi) {
                    $roommatePks = \App\Models\PenghuniKamar::where('kamar_id', $kamar->id)
                        ->where('status', 'aktif')
                        ->where('id', '!=', $penghuniKamar->id)
                        ->get();

                    foreach ($roommatePks as $rPk) {
                        $rPending = Pembayaran::where('penghuni_kamar_id', $rPk->id)
                            ->where('status', 'pending')
                            ->first();

                        if (!$rPending) {
                            Pembayaran::create([
                                'penghuni_kamar_id' => $rPk->id,
                                'jumlah' => $jumlahBiaya,
                                'porsi_bayar' => $defaultPorsi,
                                'tipe_perpanjangan' => $rPk->durasi ?? ($penghuniKamar->durasi ?? 'bulanan'),
                                'jumlah_hari' => $daysForBilling,
                                'periode_mulai' => $tanggalKeluar->toDateString(),
                                'periode_selesai' => $tanggalKeluar->copy()->addDays($daysForBilling)->toDateString(),
                                'status' => 'pending',
                            ]);
                        }
                    }
                }

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
