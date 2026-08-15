<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\WhatsAppNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    #[Test]
    public function notification_has_correct_via_channels()
    {
        $notif = new WhatsAppNotification('Test Judul', 'Test Pesan');
        $user = User::factory()->make();

        $channels = $notif->via($user);

        $this->assertContains('database', $channels);
    }

    #[Test]
    public function notification_to_database_returns_correct_structure()
    {
        $notif = new WhatsAppNotification('Pembayaran', 'Pembayaran Anda telah diverifikasi');
        $user = User::factory()->make();

        $data = $notif->toDatabase($user);

        $this->assertEquals('Pembayaran', $data['judul']);
        $this->assertEquals('Pembayaran Anda telah diverifikasi', $data['pesan']);
        $this->assertEquals('whatsapp', $data['channel']);
    }

    #[Test]
    public function notification_to_whatsapp_returns_formatted_message()
    {
        $notif = new WhatsAppNotification('Info', 'Ada pengumuman baru');
        $user = User::factory()->make(['no_hp' => '08123456789']);

        $data = $notif->toWhatsApp($user);

        $this->assertEquals('08123456789', $data['to']);
        $this->assertStringContainsString('[Info]', $data['message']);
    }
}
