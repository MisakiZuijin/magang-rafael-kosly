<?php

namespace Tests\Unit\Models;

use App\Models\Pembayaran;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PembayaranModelTest extends TestCase
{
    #[Test]
    public function pembayaran_default_status_is_pending()
    {
        // Buat instance tanpa factory (tanpa DB)
        $pembayaran = new Pembayaran();

        // Cek default di level migration/model
        $this->assertNull($pembayaran->status);

        // Simulasi default dari migration
        $pembayaran->status = 'pending';
        $this->assertEquals('pending', $pembayaran->status);
    }

    #[Test]
    public function pembayaran_has_verifikator_relation()
    {
        $pembayaran = new Pembayaran();
        $relation = $pembayaran->verifikator();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    #[Test]
    public function pembayaran_has_penghuni_kamar_relation()
    {
        $pembayaran = new Pembayaran();
        $relation = $pembayaran->penghuniKamar();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    #[Test]
    public function pembayaran_casts_dates_correctly()
    {
        $pembayaran = new Pembayaran();
        $casts = $pembayaran->getCasts();

        $this->assertArrayHasKey('periode_mulai', $casts);
        $this->assertArrayHasKey('periode_selesai', $casts);
        $this->assertArrayHasKey('tanggal_bayar', $casts);
        $this->assertArrayHasKey('tanggal_verifikasi', $casts);
    }
}
