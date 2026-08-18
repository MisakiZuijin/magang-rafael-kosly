<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim pesan WhatsApp ke daftar user ID.
     */
    public function sendBulk(array $userIds, string $judul, string $pesan): void
    {
        $users = User::whereIn('id', $userIds)->get();
        $apiKey = config('services.whatsapp.api_key');
        $endpoint = config('services.whatsapp.endpoint', 'https://api.fonnte.com/send');

        foreach ($users as $user) {
            $noHp = $user->no_hp ?? '-';
            $formattedMessage = "*[{$judul}]*\n\nHallo {$user->nama},\n\n{$pesan}\n\n_Pesan otomatis dari Kostly App_";

            // Simpan log notifikasi WA di database
            \App\Models\Notifikasi::create([
                'user_id' => $user->id,
                'judul' => '[WhatsApp] ' . $judul,
                'pesan' => $pesan,
                'channel' => 'whatsapp',
                'status' => 'terkirim',
            ]);

            // Jika API Key gateway (contoh: Fonnte / Wablas) diisi di .env
            if ($apiKey && !empty($noHp) && $noHp !== '-') {
                try {
                    Http::withHeaders([
                        'Authorization' => $apiKey,
                    ])->post($endpoint, [
                        'target' => $noHp,
                        'message' => $formattedMessage,
                    ]);
                } catch (\Throwable $e) {
                    Log::error("Gagal mengirim WhatsApp Gateway ke {$noHp}: " . $e->getMessage());
                }
            } else {
                Log::info("SIMULASI WHATSAPP [Target: {$noHp} ({$user->nama})]: {$formattedMessage}");
            }
        }
    }
}
