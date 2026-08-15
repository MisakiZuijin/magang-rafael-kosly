<?php

namespace App\Notifications;

use App\Models\Pengumuman;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengumumanNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Pengumuman $pengumuman,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'judul' => $this->pengumuman->judul,
            'pesan' => $this->pengumuman->isi,
            'tipe' => $this->pengumuman->tipe,
            'pengumuman_id' => $this->pengumuman->id,
            'channel' => 'web',
        ];
    }
}
