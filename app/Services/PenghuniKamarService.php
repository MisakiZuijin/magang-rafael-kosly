<?php

namespace App\Services;

use App\Models\Kamar;
use App\Models\PenghuniKamar;
use App\Models\Setting;
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
            } else {
                // Jika salah satu penghuni checkout (jumlah aktif menjadi 2 atau kurang), kapasitas dan metode kamar otomatis beralih ke 2 orang
                if ((int)$kamar->kapasitas !== 2) {
                    $kamar->update(['kapasitas' => 2]);
                }
            }
        }

        if ($jumlahAktif >= $kamar->kapasitas && $kamar->kapasitas > 0) {
            $this->kamarRepository->updateStatus($kamarId, 'terisi');
        } elseif ($jumlahAktif === 0) {
            $this->kamarRepository->updateStatus($kamarId, 'kosong');
        } else {
            $this->kamarRepository->updateStatus($kamarId, 'terisi');
        }
    }

    public function create(array $data): PenghuniKamar
    {
        $penghuniKamar = $this->repository->create($data);

        // Sync kapasitas dan status kamar
        $this->syncKapasitasDanStatus($data['kamar_id']);

        $kamar = $this->kamarRepository->findById($data['kamar_id']);

        // --- BUAT TAGIHAN PEMBAYARAN AWAL AUTOMATIS ---
        $tanggalMasukObj = \Carbon\Carbon::parse($penghuniKamar->tanggal_masuk)->setTime(0, 0, 0);

        if ($penghuniKamar->tanggal_keluar) {
            $tanggalKeluarObj = \Carbon\Carbon::parse($penghuniKamar->tanggal_keluar)->setTime(14, 0, 0);
        } else {
            if ($penghuniKamar->durasi === 'harian') {
                $tanggalKeluarObj = $tanggalMasukObj->copy()->addDay()->setTime(14, 0, 0);
            } elseif ($penghuniKamar->durasi === 'mingguan') {
                $tanggalKeluarObj = $tanggalMasukObj->copy()->addDays(6)->setTime(14, 0, 0);
            } else {
                $tanggalKeluarObj = $tanggalMasukObj->copy()->addDays(29)->setTime(14, 0, 0);
            }
        }

        $penghuniKamar->update([
            'tanggal_masuk' => $tanggalMasukObj->toDateTimeString(),
            'tanggal_keluar' => $tanggalKeluarObj->toDateTimeString(),
        ]);

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

    /**
     * Memeriksa seluruh notifikasi sewa (H-7 Bulanan, H-3 Bulanan & Mingguan, Jatuh Tempo, serta H+3 Pasca Jatuh Tempo).
     */
    public function periksaSemuaNotifikasiSewa(): array
    {
        $h7 = $this->periksaDanKirimNotifikasiH7();
        $h3 = $this->periksaDanKirimNotifikasiH3();
        $jatuhTempo = $this->periksaDanKirimNotifikasiJatuhTempo();
        $hplus3 = $this->periksaDanKirimNotifikasiHPlus3();

        return [
            'h7' => $h7,
            'h3' => $h3,
            'jatuh_tempo' => $jatuhTempo,
            'hplus3' => $hplus3,
        ];
    }

    /**
     * Menggabungkan nama-nama penghuni kamar menjadi satu baris teks rapi:
     * Contoh 1 orang: "Moriah Leffler"
     * Contoh 2 orang: "Penghuni A & Penghuni B"
     * Contoh 3 orang: "Penghuni A, Penghuni B & Penghuni C"
     */
    protected function formatCombinedNames(array $names): string
    {
        $filtered = array_values(array_unique(array_filter($names)));
        if (empty($filtered)) {
            return 'Penghuni';
        }
        if (count($filtered) === 1) {
            return $filtered[0];
        }
        if (count($filtered) === 2) {
            return $filtered[0] . ' & ' . $filtered[1];
        }
        $last = array_pop($filtered);
        return implode(', ', $filtered) . ' & ' . $last;
    }

    /**
     * Memeriksa dan mengirimkan notifikasi H-7 (Khusus Masa Sewa BULANAN).
     * Pesan WhatsApp dikirim 1x per kamar ke ID Grup WhatsApp Kamar dengan nama penghuni digabung.
     * Flag status tersimpan langsung pada tabel kamar (notif_h7_sent_at).
     */
    public function periksaDanKirimNotifikasiH7(): array
    {
        $now = now();
        $threeDaysAhead = $now->copy()->addDays(3)->endOfDay();
        $sevenDaysAhead = $now->copy()->addDays(7)->endOfDay();

        // Cari data penghuni_kamar yang:
        // 1. Status masih aktif
        // 2. Durasi sewa BULANAN atau HARIAN (Khusus Mingguan dikecualikan dari H-7)
        // 3. Tanggal keluar berada di rentang H-7 hingga H-4 (> 3 hari ke depan dan <= 7 hari ke depan)
        //    (Mencegah pengiriman ganda H-7 saat kamar sudah berada pada periode H-3)
        // 4. Kamar belum pernah dikirimkan notifikasi H-7 untuk periode ini (kamar.notif_h7_sent_at IS NULL)
        $h7List = PenghuniKamar::with(['penghuni', 'kamar.kos.mitra'])
            ->where('status', 'aktif')
            ->whereIn('durasi', ['bulanan', 'harian'])
            ->whereNotNull('tanggal_keluar')
            ->where('tanggal_keluar', '>', $threeDaysAhead)
            ->where('tanggal_keluar', '<=', $sevenDaysAhead)
            ->whereHas('kamar', function ($q) {
                $q->whereNull('notif_h7_sent_at');
            })
            ->get();

        $processedCount = 0;
        $whatsAppService = app(\App\Services\WhatsAppService::class);
        $appName = Setting::appName();

        // Kelompokkan per kamar agar WhatsApp hanya dikirim 1x per kamar ke ID Grup Kamar
        $groupedByKamar = $h7List->groupBy('kamar_id');

        foreach ($groupedByKamar as $kamarId => $pks) {
            $firstPk = $pks->first();
            $kamar = $firstPk->kamar ?? null;
            $kos = $kamar->kos ?? null;

            if (!$kamar) {
                continue;
            }

            $kodeKamar = $kamar->kode_kamar ?? '-';
            $kosNama = $kos->nama ?? 'Kos';
            $tglKeluarFormatted = $firstPk->tanggal_keluar ? $firstPk->tanggal_keluar->format('d/m/Y H:i') : '-';
            $sisaHari = max(1, (int)$now->diffInDays(\Carbon\Carbon::parse($firstPk->tanggal_keluar), false));

            // Ambil seluruh penghuni aktif di kamar ini agar nama selalu lengkap
            $allRoomOccupants = PenghuniKamar::where('kamar_id', $kamarId)
                ->where('status', 'aktif')
                ->with('penghuni')
                ->get();
            $names = $allRoomOccupants->map(fn($p) => $p->penghuni->nama ?? 'Penghuni')->filter()->values()->all();
            $combinedNames = $this->formatCombinedNames($names);

            // 1. Notifikasi Web ke Seluruh Penghuni Kamar
            foreach ($allRoomOccupants as $pk) {
                if ($pk->penghuni) {
                    \App\Models\Notifikasi::create([
                        'user_id' => $pk->penghuni->id,
                        'judul' => "Masa Sewa Kamar Tersisa {$sisaHari} Hari Lagi (H-7)",
                        'pesan' => "Pemberitahuan: Masa sewa Kamar {$kodeKamar} di {$kosNama} akan berakhir pada {$tglKeluarFormatted} WIB (tersisa {$sisaHari} hari). Silakan lakukan pembayaran perpanjangan sewa melalui menu Pembayaran.",
                        'channel' => 'web',
                        'status' => 'terkirim',
                    ]);
                }
            }

            // 2. Pesan WhatsApp H-7 (Hanya nama aplikasi tanpa link web)
            $waMessage = "Halo *{$combinedNames}* (Kamar *{$kodeKamar}*),\n\n"
                . "⏳ *PEMBERITAHUAN SISA MASA SEWA KOS (H-7)*\n\n"
                . "Kami menginformasikan bahwa masa sewa kamar kos Anda akan segera berakhir dalam *{$sisaHari} Hari ke depan*.\n\n"
                . "📋 *RINCIAN SEWA KAMAR:*\n"
                . "• Kos: *{$kosNama}*\n"
                . "• Kamar: *{$kodeKamar}*\n"
                . "• Batas Waktu Sewa: *{$tglKeluarFormatted} WIB*\n"
                . "• Sisa Waktu: *{$sisaHari} Hari*\n\n"
                . "💡 *PANDUAN PERPANJANGAN SEWA:*\n"
                . "1. Buka aplikasi *{$appName}*, masuk ke menu *Pembayaran*.\n"
                . "2. Pilih skema perpanjangan sewa kamar Anda.\n"
                . "3. Lakukan transfer dan unggah bukti pembayaran agar segera diverifikasi oleh Admin.\n\n"
                . "Jika Anda berencana selesai/checkout pada akhir periode ini, mohon konfirmasi kepada pihak pengelola.\n"
                . "Terima kasih atas kerja sama Anda!";

            // 3. Kirim ke Grup WhatsApp Kamar (Cukup 1x kirim ke ID Grup Kamar)
            if (!empty($kamar->wa_group_id) && $kamar->wa_group_id !== '-') {
                try {
                    $whatsAppService->sendDirect(
                        $kamar->wa_group_id,
                        "PENGINGAT MASA SEWA KAMAR {$kodeKamar} (H-7)",
                        $waMessage
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal kirim WA H-7 ke Grup Kamar {$kodeKamar} ({$kamar->wa_group_id}): " . $e->getMessage());
                }

                $kamar->update([
                    'notif_h7_sent_at' => $now,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning("Kamar {$kodeKamar} masuk H-7 namun wa_group_id kosong.");
            }

            $processedCount++;
        }

        return [
            'total_kamar_h7' => $groupedByKamar->count(),
            'processed' => $processedCount,
        ];
    }

    /**
     * Memeriksa dan mengirimkan notifikasi H-3 (Khusus Masa Sewa BULANAN dan MINGGUAN).
     * Pesan WhatsApp dikirim 1x per kamar ke ID Grup WhatsApp Kamar dengan nama penghuni digabung.
     * Flag status tersimpan langsung pada tabel kamar (notif_h3_sent_at).
     */
    public function periksaDanKirimNotifikasiH3(): array
    {
        $now = now();
        $threeDaysAhead = $now->copy()->addDays(3)->endOfDay();

        // Cari data penghuni_kamar yang:
        // 1. Status masih aktif
        // 2. Durasi sewa BULANAN, MINGGUAN, dan HARIAN
        // 3. Tanggal keluar masih di masa depan (> now) tetapi <= 3 hari ke depan
        // 4. Kamar belum pernah dikirimkan notifikasi H-3 untuk periode ini (kamar.notif_h3_sent_at IS NULL)
        $h3List = PenghuniKamar::with(['penghuni', 'kamar.kos.mitra'])
            ->where('status', 'aktif')
            ->whereIn('durasi', ['bulanan', 'mingguan', 'harian'])
            ->whereNotNull('tanggal_keluar')
            ->where('tanggal_keluar', '>', $now)
            ->where('tanggal_keluar', '<=', $threeDaysAhead)
            ->whereHas('kamar', function ($q) {
                $q->whereNull('notif_h3_sent_at');
            })
            ->get();

        $processedCount = 0;
        $whatsAppService = app(\App\Services\WhatsAppService::class);
        $appName = Setting::appName();

        // Kelompokkan per kamar agar WhatsApp hanya dikirim 1x per kamar ke ID Grup Kamar
        $groupedByKamar = $h3List->groupBy('kamar_id');

        foreach ($groupedByKamar as $kamarId => $pks) {
            $firstPk = $pks->first();
            $kamar = $firstPk->kamar ?? null;
            $kos = $kamar->kos ?? null;

            if (!$kamar) {
                continue;
            }

            $kodeKamar = $kamar->kode_kamar ?? '-';
            $kosNama = $kos->nama ?? 'Kos';
            $tglKeluarFormatted = $firstPk->tanggal_keluar ? $firstPk->tanggal_keluar->format('d/m/Y H:i') : '-';
            $sisaHari = max(1, (int)$now->diffInDays(\Carbon\Carbon::parse($firstPk->tanggal_keluar), false));

            // Ambil seluruh penghuni aktif di kamar ini agar nama selalu lengkap
            $allRoomOccupants = PenghuniKamar::where('kamar_id', $kamarId)
                ->where('status', 'aktif')
                ->with('penghuni')
                ->get();
            $names = $allRoomOccupants->map(fn($p) => $p->penghuni->nama ?? 'Penghuni')->filter()->values()->all();
            $combinedNames = $this->formatCombinedNames($names);

            // 1. Notifikasi Web ke Seluruh Penghuni Kamar
            foreach ($allRoomOccupants as $pk) {
                if ($pk->penghuni) {
                    \App\Models\Notifikasi::create([
                        'user_id' => $pk->penghuni->id,
                        'judul' => "Masa Sewa Kamar Tersisa {$sisaHari} Hari Lagi (H-3)",
                        'pesan' => "Pemberitahuan: Masa sewa Kamar {$kodeKamar} di {$kosNama} tersisa {$sisaHari} hari lagi (berakhir {$tglKeluarFormatted} WIB). Segera lakukan perpanjangan sewa melalui menu Pembayaran.",
                        'channel' => 'web',
                        'status' => 'terkirim',
                    ]);
                }
            }

            // 2. Pesan WhatsApp H-3 (Hanya nama aplikasi tanpa link web)
            $waMessage = "Halo *{$combinedNames}* (Kamar *{$kodeKamar}*),\n\n"
                . "⏳ *PEMBERITAHUAN SISA MASA SEWA KOS (H-3)*\n\n"
                . "Kami menginformasikan bahwa masa sewa kamar kos Anda tersisa *{$sisaHari} Hari lagi*.\n\n"
                . "📋 *RINCIAN SEWA KAMAR:*\n"
                . "• Kos: *{$kosNama}*\n"
                . "• Kamar: *{$kodeKamar}*\n"
                . "• Batas Waktu Sewa: *{$tglKeluarFormatted} WIB*\n"
                . "• Sisa Waktu: *{$sisaHari} Hari*\n\n"
                . "💡 *PANDUAN PERPANJANGAN SEWA:*\n"
                . "1. Buka aplikasi *{$appName}*, masuk ke menu *Pembayaran*.\n"
                . "2. Pilih skema perpanjangan sewa kamar Anda.\n"
                . "3. Lakukan transfer dan unggah bukti pembayaran agar segera diverifikasi oleh Admin.\n\n"
                . "Jika Anda berencana selesai/checkout pada akhir periode ini, mohon segera konfirmasi kepada pihak pengelola.\n"
                . "Terima kasih atas kerja sama Anda!";

            // 3. Kirim ke Grup WhatsApp Kamar (Cukup 1x kirim ke ID Grup Kamar)
            if (!empty($kamar->wa_group_id) && $kamar->wa_group_id !== '-') {
                try {
                    $whatsAppService->sendDirect(
                        $kamar->wa_group_id,
                        "PENGINGAT MASA SEWA KAMAR {$kodeKamar} (H-3)",
                        $waMessage
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal kirim WA H-3 ke Grup Kamar {$kodeKamar} ({$kamar->wa_group_id}): " . $e->getMessage());
                }

                $kamar->update([
                    'notif_h3_sent_at' => $now,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning("Kamar {$kodeKamar} masuk H-3 namun wa_group_id kosong.");
            }

            $processedCount++;
        }

        return [
            'total_kamar_h3' => $groupedByKamar->count(),
            'processed' => $processedCount,
        ];
    }

    /**
     * Memeriksa dan mengirimkan notifikasi Web & WhatsApp untuk sewa kamar yang telah jatuh tempo (waktunya habis).
     * Pesan WhatsApp dikirim 1x per kamar ke ID Grup WhatsApp Kamar dengan nama penghuni digabung.
     * Flag status tersimpan langsung pada tabel kamar (notif_jatuh_tempo_sent_at).
     */
    public function periksaDanKirimNotifikasiJatuhTempo(): array
    {
        $now = now();
        $threeDaysAgo = $now->copy()->subDays(3);

        // Cari seluruh data penghuni_kamar yang:
        // 1. Status masih aktif
        // 2. Tanggal keluar berada di rentang Jatuh Tempo (H-0 hingga sebelum H+3):
        //    (tanggal_keluar <= now dan tanggal_keluar > 3 hari yang lalu)
        //    (Mencegah pengiriman ganda pesan jatuh tempo saat kamar sudah masuk periode H+3)
        // 3. Kamar belum pernah dikirimkan notifikasi jatuh tempo untuk periode ini (kamar.notif_jatuh_tempo_sent_at IS NULL)
        $expiredList = PenghuniKamar::with(['penghuni', 'kamar.kos.mitra'])
            ->where('status', 'aktif')
            ->whereNotNull('tanggal_keluar')
            ->where('tanggal_keluar', '<=', $now)
            ->where('tanggal_keluar', '>', $threeDaysAgo)
            ->whereHas('kamar', function ($q) {
                $q->whereNull('notif_jatuh_tempo_sent_at');
            })
            ->get();

        $processedCount = 0;
        $whatsAppService = app(\App\Services\WhatsAppService::class);
        $appName = Setting::appName();

        // Kelompokkan per kamar agar WhatsApp hanya dikirim 1x per kamar ke ID Grup Kamar
        $groupedByKamar = $expiredList->groupBy('kamar_id');

        foreach ($groupedByKamar as $kamarId => $pks) {
            $firstPk = $pks->first();
            $kamar = $firstPk->kamar ?? null;
            $kos = $kamar->kos ?? null;

            if (!$kamar) {
                continue;
            }

            $kodeKamar = $kamar->kode_kamar ?? '-';
            $kosNama = $kos->nama ?? 'Kos';
            $tglKeluarFormatted = $firstPk->tanggal_keluar ? $firstPk->tanggal_keluar->format('d/m/Y H:i') : '-';

            // Ambil seluruh penghuni aktif di kamar ini agar nama selalu lengkap
            $allRoomOccupants = PenghuniKamar::where('kamar_id', $kamarId)
                ->where('status', 'aktif')
                ->with('penghuni')
                ->get();
            $names = $allRoomOccupants->map(fn($p) => $p->penghuni->nama ?? 'Penghuni')->filter()->values()->all();
            $combinedNames = $this->formatCombinedNames($names);

            // 1. Kirim Notifikasi Web ke Seluruh Penghuni Kamar
            foreach ($allRoomOccupants as $pk) {
                if ($pk->penghuni) {
                    \App\Models\Notifikasi::create([
                        'user_id' => $pk->penghuni->id,
                        'judul' => 'Masa Sewa Kamar Telah Jatuh Tempo',
                        'pesan' => "Perhatian: Masa sewa Kamar {$kodeKamar} di {$kosNama} telah berakhir pada {$tglKeluarFormatted} WIB. Silakan lakukan perpanjangan sewa melalui menu Pembayaran atau konfirmasi penyelesaian sewa.",
                        'channel' => 'web',
                        'status' => 'terkirim',
                    ]);
                }
            }

            // 2. Pesan WhatsApp Jatuh Tempo (Hanya nama aplikasi tanpa link web)
            $waMessage = "Halo *{$combinedNames}* (Kamar *{$kodeKamar}*),\n\n"
                . "⚠️ *PEMBERITAHUAN MASA SEWA JATUH TEMPO*\n\n"
                . "Kami informasikan bahwa masa sewa kamar kos Anda telah *BERAKHIR / JATUH TEMPO*.\n\n"
                . "📋 *RINCIAN SEWA KAMAR:*\n"
                . "• Kos: *{$kosNama}*\n"
                . "• Kamar: *{$kodeKamar}*\n"
                . "• Batas Waktu Sewa: *{$tglKeluarFormatted} WIB*\n\n"
                . "💡 *PANDUAN SELANJUTNYA:*\n"
                . "1. Jika ingin *MEMPERPANJANG SEWA*, silakan buka aplikasi *{$appName}*, masuk ke menu *Pembayaran*, lalu lakukan transfer dan upload bukti pembayaran perpanjangan sewa Anda.\n"
                . "2. Jika Anda *SUDAH SELESAI / CHECKOUT*, silakan konfirmasi kepada pihak pengelola/admin kos.\n\n"
                . "Terima kasih atas kerja sama Anda!";

            // 3. Kirim ke Grup WhatsApp Kamar (Cukup 1x kirim ke ID Grup Kamar)
            if (!empty($kamar->wa_group_id) && $kamar->wa_group_id !== '-') {
                try {
                    $whatsAppService->sendDirect(
                        $kamar->wa_group_id,
                        "PERINGATAN JATUH TEMPO SEWA KAMAR {$kodeKamar}",
                        $waMessage
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal kirim WA jatuh tempo ke Grup Kamar {$kodeKamar} ({$kamar->wa_group_id}): " . $e->getMessage());
                }

                $kamar->update([
                    'notif_jatuh_tempo_sent_at' => $now,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning("Kamar {$kodeKamar} jatuh tempo namun wa_group_id kosong.");
            }

            // 4. Kirim Notifikasi Web ke Mitra Kos
            if ($kos && $kos->mitra) {
                \App\Models\Notifikasi::create([
                    'user_id' => $kos->mitra->id,
                    'judul' => "Masa Sewa Penghuni Kamar {$kodeKamar} Jatuh Tempo",
                    'pesan' => "Masa sewa penghuni {$combinedNames} di Kamar {$kodeKamar} ({$kosNama}) telah habis per {$tglKeluarFormatted} WIB.",
                    'channel' => 'web',
                    'status' => 'terkirim',
                ]);
            }

            $processedCount++;
        }

        return [
            'total_kamar_expired' => $groupedByKamar->count(),
            'processed' => $processedCount,
        ];
    }

    /**
     * Memeriksa dan mengirimkan notifikasi H+3 Pasca Jatuh Tempo (Himbauan Pelunasan / Checkout).
     * Dijalankan untuk kamar yang masa sewanya telah terlewat >= 3 hari setelah tanggal jatuh tempo.
     * Pesan WhatsApp dikirim 1x per kamar ke ID Grup WhatsApp Kamar dengan nama penghuni digabung.
     * Flag status tersimpan langsung pada tabel kamar (notif_hplus3_sent_at).
     */
    public function periksaDanKirimNotifikasiHPlus3(): array
    {
        $now = now();
        $threeDaysAgo = $now->copy()->subDays(3);

        // Cari seluruh data penghuni_kamar yang:
        // 1. Status masih aktif
        // 2. Tanggal keluar sudah lewat minimal 3 hari yang lalu (tanggal_keluar <= 3 hari yang lalu)
        // 3. Kamar belum pernah dikirimkan notifikasi H+3 untuk periode ini (kamar.notif_hplus3_sent_at IS NULL)
        $overdueList = PenghuniKamar::with(['penghuni', 'kamar.kos.mitra'])
            ->where('status', 'aktif')
            ->whereNotNull('tanggal_keluar')
            ->where('tanggal_keluar', '<=', $threeDaysAgo)
            ->whereHas('kamar', function ($q) {
                $q->whereNull('notif_hplus3_sent_at');
            })
            ->get();

        $processedCount = 0;
        $whatsAppService = app(\App\Services\WhatsAppService::class);
        $appName = Setting::appName();

        // Kelompokkan per kamar agar WhatsApp hanya dikirim 1x per kamar ke ID Grup Kamar
        $groupedByKamar = $overdueList->groupBy('kamar_id');

        foreach ($groupedByKamar as $kamarId => $pks) {
            $firstPk = $pks->first();
            $kamar = $firstPk->kamar ?? null;
            $kos = $kamar->kos ?? null;

            if (!$kamar) {
                continue;
            }

            $kodeKamar = $kamar->kode_kamar ?? '-';
            $kosNama = $kos->nama ?? 'Kos';
            $tglKeluarFormatted = $firstPk->tanggal_keluar ? $firstPk->tanggal_keluar->format('d/m/Y H:i') : '-';
            $terlewatHari = max(3, (int)\Carbon\Carbon::parse($firstPk->tanggal_keluar)->diffInDays($now));

            // Ambil seluruh penghuni aktif di kamar ini agar nama selalu lengkap
            $allRoomOccupants = PenghuniKamar::where('kamar_id', $kamarId)
                ->where('status', 'aktif')
                ->with('penghuni')
                ->get();
            $names = $allRoomOccupants->map(fn($p) => $p->penghuni->nama ?? 'Penghuni')->filter()->values()->all();
            $combinedNames = $this->formatCombinedNames($names);

            // 1. Kirim Notifikasi Web ke Seluruh Penghuni Kamar
            foreach ($allRoomOccupants as $pk) {
                if ($pk->penghuni) {
                    \App\Models\Notifikasi::create([
                        'user_id' => $pk->penghuni->id,
                        'judul' => "Himbauan Penyelesaian Sewa Kamar (Terlewat {$terlewatHari} Hari)",
                        'pesan' => "Himbauan: Masa sewa Kamar {$kodeKamar} di {$kosNama} telah terlewat {$terlewatHari} hari (jatuh tempo pada {$tglKeluarFormatted} WIB). Mohon segera melakukan pembayaran perpanjangan sewa melalui menu Pembayaran atau lakukan konfirmasi checkout/pengosongan kamar.",
                        'channel' => 'web',
                        'status' => 'terkirim',
                    ]);
                }
            }

            // 2. Pesan WhatsApp H+3 (Himbauan Pelunasan / Checkout)
            $waMessage = "Halo *{$combinedNames}* (Kamar *{$kodeKamar}*),\n\n"
                . "📢 *HIMBAUAN PENYELESAIAN SEWA KAMAR (H+3)*\n\n"
                . "Kami menginformasikan bahwa masa sewa kamar kos Anda telah *TERLEWAT {$terlewatHari} HARI* sejak tanggal jatuh tempo (*{$tglKeluarFormatted} WIB*).\n\n"
                . "📋 *RINCIAN SEWA KAMAR:*\n"
                . "• Kos: *{$kosNama}*\n"
                . "• Kamar: *{$kodeKamar}*\n"
                . "• Tanggal Jatuh Tempo: *{$tglKeluarFormatted} WIB*\n"
                . "• Status: *Terlewat {$terlewatHari} Hari*\n\n"
                . "💡 *HIMBAUAN & TINDAK LANJUT:*\n"
                . "1. Jika bermaksud *MEMPERPANJANG SEWA*, mohon segera buka aplikasi *{$appName}*, masuk ke menu *Pembayaran*, dan selesaikan pembayaran perpanjangan sewa Anda.\n"
                . "2. Jika *TIDAK MEMPERPANJANG SEWA*, mohon segera lakukan proses checkout / pengosongan kamar dan konfirmasi kepada pihak pengelola/admin kos.\n\n"
                . "Mohon kerja samanya agar pengelolaan kamar dapat berjalan tertib dan lancar. Terima kasih!";

            // 3. Kirim ke Grup WhatsApp Kamar (Cukup 1x kirim ke ID Grup Kamar)
            if (!empty($kamar->wa_group_id) && $kamar->wa_group_id !== '-') {
                try {
                    $whatsAppService->sendDirect(
                        $kamar->wa_group_id,
                        "HIMBAUAN PENYELESAIAN SEWA KAMAR {$kodeKamar} (H+3)",
                        $waMessage
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal kirim WA H+3 ke Grup Kamar {$kodeKamar} ({$kamar->wa_group_id}): " . $e->getMessage());
                }

                $kamar->update([
                    'notif_hplus3_sent_at' => $now,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning("Kamar {$kodeKamar} masuk H+3 namun wa_group_id kosong.");
            }

            // 4. Kirim Notifikasi Web ke Mitra Kos
            if ($kos && $kos->mitra) {
                \App\Models\Notifikasi::create([
                    'user_id' => $kos->mitra->id,
                    'judul' => "Himbauan: Sewa Kamar {$kodeKamar} Terlewat 3 Hari",
                    'pesan' => "Masa sewa penghuni {$combinedNames} di Kamar {$kodeKamar} ({$kosNama}) telah terlewat {$terlewatHari} hari sejak {$tglKeluarFormatted} WIB dan belum diperpanjang.",
                    'channel' => 'web',
                    'status' => 'terkirim',
                ]);
            }

            $processedCount++;
        }

        return [
            'total_kamar_hplus3' => $groupedByKamar->count(),
            'processed' => $processedCount,
        ];
    }
}
