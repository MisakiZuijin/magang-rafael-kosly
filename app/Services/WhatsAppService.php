<?php

namespace App\Services;

use App\Models\Kamar;
use App\Models\Kos;
use App\Models\PenghuniKamar;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Dapatkan API Key Fonnte dari DB settings, user Mitra Pro, atau env.
     */
    public function getApiKey(?User $user = null): ?string
    {
        if ($user && $user->is_pro && !empty($user->wa_gateway_token)) {
            return $user->wa_gateway_token;
        }
        return Setting::getByKey('fonnte_api_key', config('services.whatsapp.api_key'));
    }

    /**
     * Dapatkan Endpoint Fonnte dari DB settings, user Mitra Pro, atau default.
     */
    public function getEndpoint(?User $user = null): string
    {
        if ($user && $user->is_pro && !empty($user->wa_gateway_endpoint)) {
            return $user->wa_gateway_endpoint;
        }
        return Setting::getByKey('fonnte_endpoint', config('services.whatsapp.endpoint', 'https://api.fonnte.com/send'));
    }

    /**
     * Cek status device Fonnte secara langsung via API Fonnte.
     */
    public function checkDeviceStatus(?string $customApiKey = null): array
    {
        $apiKey = $customApiKey ?: $this->getApiKey();
        if (!$apiKey) {
            return [
                'connected' => false,
                'status_text' => 'API Token Belum Dikonfigurasi',
                'message' => 'Silakan masukkan API Token Fonnte terlebih dahulu.',
                'raw' => null,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
            ])->timeout(8)->post('https://api.fonnte.com/device');

            if ($response->successful()) {
                $data = $response->json();
                $deviceStatus = strtolower((string)($data['device_status'] ?? ''));
                $isConnect = in_array($deviceStatus, ['connect', 'connected'], true);
                
                return [
                    'connected' => $isConnect,
                    'status_text' => $isConnect ? 'Connected (Terhubung)' : 'Disconnected (Belum Scan / Putus)',
                    'device' => $data['device'] ?? '-',
                    'name' => $data['name'] ?? '-',
                    'package' => $data['package'] ?? '-',
                    'quota' => $data['quota'] ?? 0,
                    'expired' => $data['expired'] ?? '-',
                    'message' => $isConnect ? '' : 'Nomor WhatsApp terputus atau belum di-scan (disconnect). Silakan lakukan Scan QR Code nomor pengelola di fonnte.com.',
                    'raw' => $data,
                ];
            } else {
                return [
                    'connected' => false,
                    'status_text' => 'Gagal Konek ke Fonnte (HTTP ' . $response->status() . ')',
                    'message' => $response->body(),
                    'raw' => null,
                ];
            }
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'status_text' => 'Error Server Fonnte: ' . $e->getMessage(),
                'message' => $e->getMessage(),
                'raw' => null,
            ];
        }
    }

    /**
     * Kirim pesan langsung ke 1 target (nomor HP atau ID Grup WA Fonnte).
     */
    public function sendDirect(string $target, string $judul, string $pesan, ?string $customApiKey = null, ?string $customEndpoint = null): array
    {
        $apiKey = $customApiKey ?: $this->getApiKey();
        $endpoint = $customEndpoint ?: $this->getEndpoint();
        $appName = Setting::appName();
        $formattedMessage = "*[{$judul}]*\n\n{$pesan}\n\n_Pesan otomatis dari {$appName} App_";

        if ($apiKey && !empty($target) && $target !== '-') {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                ])->post($endpoint, [
                    'target' => $target,
                    'message' => $formattedMessage,
                ]);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'message' => 'Pesan WhatsApp berhasil dikirim ke ' . $target,
                        'data' => $response->json(),
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Fonnte mengembalikan status HTTP ' . $response->status() . ': ' . $response->body(),
                    ];
                }
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim WhatsApp ke {$target}: " . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Gagal mengirim pesan: ' . $e->getMessage(),
                ];
            }
        } else {
            Log::info("SIMULASI WHATSAPP [Target: {$target}]: {$formattedMessage}");
            return [
                'success' => true,
                'message' => 'Pesan tersimulasi (API Token Fonnte belum diisi). Check storage/logs/laravel.log.',
            ];
        }
    }

    /**
     * Kirim pesan WhatsApp ke daftar user ID (PM).
     */
    public function sendBulk(array $userIds, string $judul, string $pesan): void
    {
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $noHp = $user->no_hp ?? '-';

            // Simpan log notifikasi WA di database
            \App\Models\Notifikasi::create([
                'user_id' => $user->id,
                'judul' => '[WhatsApp] ' . $judul,
                'pesan' => $pesan,
                'channel' => 'whatsapp',
                'status' => 'terkirim',
            ]);

            if (!empty($noHp) && $noHp !== '-') {
                $this->sendDirect($noHp, $judul, $pesan);
            }
        }
    }

    /**
     * Kirim pengumuman WhatsApp dengan pembatasan laju (rate limiting / batching):
     * Setiap 5 pesan WhatsApp terkirim diberi jeda 1 menit (60 detik) agar nomor tidak terblokir/banned.
     *
     * @param array $items Array asosiatif berisi ['target' => string, 'judul' => string, 'pesan' => string, 'user_id' => int|null]
     * @return int Jumlah pesan yang berhasil diproses
     */
    public function sendPengumumanWithThrottle(array $items, ?string $customApiKey = null, ?string $customEndpoint = null): int
    {
        @set_time_limit(0); // Menghindari script timeout karena jeda pengiriman
        $sentCount = 0;
        $total = count($items);

        foreach ($items as $index => $item) {
            $target = $item['target'] ?? null;
            $judul = $item['judul'] ?? 'PENGUMUMAN';
            $pesan = $item['pesan'] ?? '';
            $userId = $item['user_id'] ?? null;

            if (empty($target) || $target === '-') {
                continue;
            }

            // Catat log notifikasi WA jika ada user_id terkait
            if ($userId) {
                \App\Models\Notifikasi::create([
                    'user_id' => $userId,
                    'judul' => '[WhatsApp] ' . $judul,
                    'pesan' => $pesan,
                    'channel' => 'whatsapp',
                    'status' => 'terkirim',
                ]);
            }

            $this->sendDirect($target, $judul, $pesan, $customApiKey, $customEndpoint);
            $sentCount++;

            // Jika kelipatan 5 pesan dan masih ada pesan tersisa yang harus dikirim, jeda 1 menit (60 detik)
            if ($sentCount > 0 && $sentCount % 5 === 0 && ($index + 1) < $total) {
                Log::info("Rate limit Pengumuman WhatsApp: memberi jeda 60 detik setelah {$sentCount} pesan terkirim.");
                sleep(60);
            }
        }

        return $sentCount;
    }

    /**
     * Format nomor HP agar standar internasional WhatsApp (62...).
     */
    public static function formatPhoneNumber(?string $noHp): string
    {
        if (empty($noHp)) {
            return '';
        }
        $phone = preg_replace('/[^0-9]/', '', (string)$noHp);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '628' . substr($phone, 1);
        }
        return $phone;
    }

    /**
     * Generate template teks WhatsApp ke Penghuni berdasarkan status (Belum Bayar Awal, Jatuh Tempo/Lewat, Lunas/Info Umum).
     */
    public static function generatePenghuniMessage(?User $penghuni, ?PenghuniKamar $penghuniKamar = null, ?User $sender = null, ?Kamar $kamar = null, ?Kos $kos = null): string
    {
        $penghuniNama = $penghuni->nama ?? 'Penghuni';
        $currentAuth = Auth::user();
        $senderUser = $sender ?: $currentAuth;
        $senderNama = $senderUser ? ($senderUser->nama ?? 'Pengelola') : 'Pengelola Kos';
        $isAdmin = $senderUser && in_array($senderUser->role, ['admin', 'superadmin', 'super_admin']);
        $senderRole = $isAdmin ? 'Admin Kostly' : 'Pengelola/Pemilik Kos';
        $appName = Setting::appName();

        if ($penghuniKamar) {
            $resolvedKamar = $kamar ?: ($penghuniKamar->relationLoaded('kamar') ? $penghuniKamar->kamar : $penghuniKamar->kamar);
            $resolvedKos = $kos ?: ($resolvedKamar ? ($resolvedKamar->relationLoaded('kos') ? $resolvedKamar->kos : $resolvedKamar->kos) : null);
            $kodeKamar = $resolvedKamar->kode_kamar ?? '-';
            $kosNama = $resolvedKos->nama ?? 'Kos';
            $durasi = ucfirst($penghuniKamar->durasi ?? 'bulanan');
            $tglKeluarFormatted = $penghuniKamar->tanggal_keluar ? $penghuniKamar->tanggal_keluar->format('d/m/Y') : '-';

            // Hitung sisa hari sewa
            $today = \Carbon\Carbon::now()->startOfDay();
            $tglKeluar = $penghuniKamar->tanggal_keluar ? \Carbon\Carbon::parse($penghuniKamar->tanggal_keluar)->startOfDay() : null;
            $sisaHari = $tglKeluar ? (int) $today->diffInDays($tglKeluar, false) : 999;

            // Dapatkan info status pembayaran
            $statusInfo = $penghuniKamar->getStatusPembayaranInfo($resolvedKamar);

            // KONDISI 1: Belum Bayar Biaya Awal
            if ($statusInfo['status'] === 'belum_bayar_awal') {
                return "Assalamualaikum Kak *{$penghuniNama}*,\n\n"
                    . "Salam dari *{$senderRole} {$senderNama}* (*{$kosNama}* - Kamar *{$kodeKamar}*).\n\n"
                    . "Kami ingin mengonfirmasi terkait pembayaran awal sewa kamar Kakak ({$durasi}). Mohon dapat segera menyelesaikan pembayaran dan mengunggah bukti transfer melalui aplikasi *{$appName}* agar kamar dapat segera kami siapkan dan diverifikasi.\n\n"
                    . "Jika ada kendala atau pertanyaan mengenai pembayaran, silakan hubungi kami ya. Terima kasih! 🙏";
            }

            // KONDISI 2: Jatuh Tempo / Lewat Masa Sewa / Mendekati Jatuh Tempo (H-3 s.d. lewat)
            if ($sisaHari <= 3) {
                $statusSewaTeks = $sisaHari < 0 
                    ? "telah berakhir/terlewat " . abs($sisaHari) . " hari yang lalu (jatuh tempo pada {$tglKeluarFormatted})" 
                    : ($sisaHari === 0 
                        ? "jatuh tempo hari ini ({$tglKeluarFormatted})" 
                        : "akan segera berakhir dalam {$sisaHari} hari ke depan (pada {$tglKeluarFormatted})");

                return "Assalamualaikum Kak *{$penghuniNama}*,\n\n"
                    . "Salam dari *{$senderRole} {$senderNama}* (*{$kosNama}* - Kamar *{$kodeKamar}*).\n\n"
                    . "Pemberitahuan bahwa masa sewa kamar Kakak di *{$kosNama}* (Kamar *{$kodeKamar}*) {$statusSewaTeks}.\n\n"
                    . "Apakah Kakak berencana untuk memperpanjang sewa kamar? Jika ingin memperpanjang, silakan lakukan pembayaran perpanjangan sewa melalui aplikasi *{$appName}*. Jika berencana untuk checkout / selesai sewa, mohon konfirmasikan kepada kami ya.\n\n"
                    . "Terima kasih atas kerja sama Anda! 🙏";
            }

            // KONDISI 3: Lunas / Tidak ada tanggungan / Sapaan Informasi Umum
            return "Assalamualaikum Kak *{$penghuniNama}*,\n\n"
                . "Salam dari *{$senderRole} {$senderNama}* (*{$kosNama}* - Kamar *{$kodeKamar}*).\n\n"
                . "Semoga Kakak nyaman tinggal di *{$kosNama}*. Ada yang bisa kami bantu atau ada informasi yang ingin disampaikan terkait fasilitas dan kenyamanan kamar Kakak?\n\n"
                . "Terima kasih! 🙏";
        }

        // KONDISI 4: Jika hanya data user penghuni (tanpa relasi kamar)
        return "Assalamualaikum Kak *{$penghuniNama}*,\n\n"
            . "Salam dari *{$senderRole} {$senderNama}* (*{$appName}*).\n\n"
            . "Ada hal atau informasi yang ingin kami koordinasikan dengan Kakak terkait hunian dan akun sewa di *{$appName}*.\n\n"
            . "Terima kasih! 🙏";
    }

    /**
     * Generate template teks WhatsApp ke Mitra Kos (Pembuka Komunikasi).
     */
    public static function generateMitraMessage(?User $mitra, ?Kos $kos = null, ?User $sender = null): string
    {
        $mitraNama = $mitra->nama ?? 'Mitra Kos';
        $currentAuth = Auth::user();
        $senderUser = $sender ?: $currentAuth;
        $senderNama = $senderUser ? ($senderUser->nama ?? 'Admin') : 'Admin Kostly';
        $appName = Setting::appName();
        $kosInfo = $kos ? " terkait properti *{$kos->nama}*" : '';

        return "Assalamualaikum Bapak/Ibu *{$mitraNama}*,\n\n"
            . "Salam dari Admin *{$appName}* (*{$senderNama}*).\n\n"
            . "Semoga Bapak/Ibu sehat selalu. Kami ingin berkoordinasi dan mengawali komunikasi{$kosInfo} di platform *{$appName}*.\n\n"
            . "Apakah ada waktu luang untuk berdiskusi sejenak? Terima kasih! 🙏";
    }

    /**
     * Generate URL WhatsApp link langsung ke Penghuni.
     */
    public static function generatePenghuniUrl(?User $penghuni, ?PenghuniKamar $penghuniKamar = null, ?User $sender = null, ?Kamar $kamar = null, ?Kos $kos = null): string
    {
        $phone = static::formatPhoneNumber($penghuni->no_hp ?? '');
        if (empty($phone)) {
            return '#';
        }
        $message = static::generatePenghuniMessage($penghuni, $penghuniKamar, $sender, $kamar, $kos);
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }

    /**
     * Generate URL WhatsApp link langsung ke Mitra.
     */
    public static function generateMitraUrl(?User $mitra, ?Kos $kos = null, ?User $sender = null): string
    {
        $phone = static::formatPhoneNumber($mitra->no_hp ?? '');
        if (empty($phone)) {
            return '#';
        }
        $message = static::generateMitraMessage($mitra, $kos, $sender);
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }
}
