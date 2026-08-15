<?php

namespace Tests\Unit\Notifications;

use App\Models\Pembayaran;
use App\Models\User;
use App\Notifications\PembayaranVerifiedNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PembayaranVerifiedNotificationTest extends TestCase
{
    #[Test]
    public function verified_notification_has_correct_message()
    {
        // Pakai stdClass untuk hindari Mockery strict mode
        $pembayaran = new \stdClass();
        $pembayaran->jumlah = 1000000;
        $pembayaran->id = 1;

        $notif = new PembayaranVerifiedNotification(
            \App\Models\Pembayaran::unguarded(fn() => new Pembayaran(['jumlah' => 1000000, 'id' => 1])),
            'terverifikasi'
        );
        $user = User::factory()->make();

        $data = $notif->toDatabase($user);

        $this->assertEquals('Pembayaran Terverifikasi', $data['judul']);
        $this->assertStringContainsString('Rp 1.000.000', $data['pesan']);
        $this->assertEquals(1, $data['pembayaran_id']);
    }

    #[Test]
    public function rejected_notification_has_correct_message()
    {
        $notif = new PembayaranVerifiedNotification(
            \App\Models\Pembayaran::unguarded(fn() => new Pembayaran([
                'jumlah' => 500000,
                'id' => 2,
                'catatan_verifikasi' => 'Bukti tidak jelas',
            ])),
            'ditolak'
        );
        $user = User::factory()->make();

        $data = $notif->toDatabase($user);

        $this->assertEquals('Pembayaran Ditolak', $data['judul']);
        $this->assertStringContainsString('Bukti tidak jelas', $data['pesan']);
    }
}
