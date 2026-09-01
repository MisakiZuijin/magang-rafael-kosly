<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Dapatkan API Key Fonnte dari DB settings atau env.
     */
    public function getApiKey(): ?string
    {
        return Setting::getByKey('fonnte_api_key', config('services.whatsapp.api_key'));
    }

    /**
     * Dapatkan Endpoint Fonnte dari DB settings atau default.
     */
    public function getEndpoint(): string
    {
        return Setting::getByKey('fonnte_endpoint', config('services.whatsapp.endpoint', 'https://api.fonnte.com/send'));
    }

    /**
     * Cek status device Fonnte secara langsung via API Fonnte.
     */
    public function checkDeviceStatus(): array
    {
        $apiKey = $this->getApiKey();
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
    public function sendDirect(string $target, string $judul, string $pesan): array
    {
        $apiKey = $this->getApiKey();
        $endpoint = $this->getEndpoint();
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
    public function sendPengumumanWithThrottle(array $items): int
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

            $this->sendDirect($target, $judul, $pesan);
            $sentCount++;

            // Jika kelipatan 5 pesan dan masih ada pesan tersisa yang harus dikirim, jeda 1 menit (60 detik)
            if ($sentCount > 0 && $sentCount % 5 === 0 && ($index + 1) < $total) {
                Log::info("Rate limit Pengumuman WhatsApp: memberi jeda 60 detik setelah {$sentCount} pesan terkirim.");
                sleep(60);
            }
        }

        return $sentCount;
    }
}
