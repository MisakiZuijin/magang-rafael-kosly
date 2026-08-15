<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WhatsAppNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $judul,
        private string $pesan,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'channel' => 'whatsapp',
        ];
    }

    // Method untuk integrasi dengan API WhatsApp (Twilio, Fonnte, dll)
    public function toWhatsApp(object $notifiable): array
    {
        return [
            'to' => $notifiable->no_hp,
            'message' => "*[{$this->judul}]*\n\n{$this->pesan}",
        ];
    }
}
