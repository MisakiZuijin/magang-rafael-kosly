<?php

namespace App\Notifications;

use App\Models\Pembayaran;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PembayaranVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Pembayaran $pembayaran,
        private string $status, // 'terverifikasi' atau 'ditolak'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $jumlah = number_format($this->pembayaran->jumlah, 0, ',', '.');

        if ($this->status === 'terverifikasi') {
            return [
                'judul' => 'Pembayaran Terverifikasi',
                'pesan' => "Pembayaran sebesar Rp {$jumlah} telah diverifikasi oleh admin.",
                'pembayaran_id' => $this->pembayaran->id,
                'channel' => 'web',
            ];
        }

        return [
            'judul' => 'Pembayaran Ditolak',
            'pesan' => "Pembayaran sebesar Rp {$jumlah} ditolak. Alasan: {$this->pembayaran->catatan_verifikasi}",
            'pembayaran_id' => $this->pembayaran->id,
            'channel' => 'web',
        ];
    }
}
