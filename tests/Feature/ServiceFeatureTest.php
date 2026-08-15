<?php

namespace Tests\Feature;

use App\Services\DashboardService;
use App\Services\MapService;
use App\Services\PembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function dashboard_service_returns_admin_stats()
    {
        $service = app(DashboardService::class);
        $stats = $service->getAdminStats();

        $this->assertArrayHasKey('occupancy', $stats);
        $this->assertArrayHasKey('payments', $stats);
        $this->assertArrayHasKey('expiring_soon', $stats);
        $this->assertArrayHasKey('total_users', $stats);
    }

    #[Test]
    public function dashboard_service_returns_mitra_stats()
    {
        $mitra = \App\Models\User::where('role', 'mitra')->first();
        $service = app(DashboardService::class);
        $stats = $service->getMitraStats($mitra->id);

        $this->assertArrayHasKey('total_kamar', $stats);
        $this->assertArrayHasKey('terisi', $stats);
        $this->assertArrayHasKey('kosong', $stats);
    }

    #[Test]
    public function map_service_returns_all_locations()
    {
        $service = app(MapService::class);
        $locations = $service->getAllLocations();

        $this->assertIsArray($locations);
        $this->assertGreaterThan(0, count($locations));

        if (count($locations) > 0) {
            $this->assertArrayHasKey('lat', $locations[0]);
            $this->assertArrayHasKey('lng', $locations[0]);
            $this->assertArrayHasKey('nama', $locations[0]);
        }
    }

    #[Test]
    public function pembayaran_service_returns_pending_payments()
    {
        $service = app(PembayaranService::class);
        $pending = $service->getPendingPayments();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $pending);
    }

    #[Test]
    public function pembayaran_service_returns_payment_stats()
    {
        $service = app(PembayaranService::class);
        $stats = $service->getStats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('terverifikasi', $stats);
        $this->assertArrayHasKey('ditolak', $stats);
        $this->assertArrayHasKey('total_nominal', $stats);
    }
}
