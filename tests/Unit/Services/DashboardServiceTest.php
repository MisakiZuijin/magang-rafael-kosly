<?php

namespace Tests\Unit\Services;

use App\Repositories\Contracts\KamarRepositoryInterface;
use App\Repositories\Contracts\PembayaranRepositoryInterface;
use App\Repositories\Contracts\PenghuniKamarRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class DashboardServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function get_admin_stats_returns_correct_structure()
    {
        $kamarRepo = Mockery::mock(KamarRepositoryInterface::class);
        $pembayaranRepo = Mockery::mock(PembayaranRepositoryInterface::class);
        $penghuniKamarRepo = Mockery::mock(PenghuniKamarRepositoryInterface::class);
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $kamarRepo->shouldReceive('getOccupancyStats')->once()->andReturn([
            'total' => 10, 'terisi' => 5, 'kosong' => 5, 'persentase_terisi' => 50
        ]);
        $pembayaranRepo->shouldReceive('getPaymentStats')->once()->andReturn([
            'total' => 20, 'pending' => 5, 'terverifikasi' => 15, 'ditolak' => 0, 'total_nominal' => 15000000
        ]);
        // Return Eloquent\Collection, bukan Support\Collection
        $penghuniKamarRepo->shouldReceive('getExpiringSoon')->with(7)->once()->andReturn(new EloquentCollection());
        $userRepo->shouldReceive('getByRole')->with('mitra')->once()->andReturn(new EloquentCollection([1,2,3]));
        $userRepo->shouldReceive('getByRole')->with('penghuni')->once()->andReturn(new EloquentCollection([1,2,3,4,5]));
        $userRepo->shouldReceive('getByRole')->with('admin')->once()->andReturn(new EloquentCollection([1]));

        $service = new DashboardService($kamarRepo, $pembayaranRepo, $penghuniKamarRepo, $userRepo);
        $stats = $service->getAdminStats();

        $this->assertArrayHasKey('occupancy', $stats);
        $this->assertArrayHasKey('payments', $stats);
        $this->assertArrayHasKey('expiring_soon', $stats);
        $this->assertArrayHasKey('total_users', $stats);
        $this->assertEquals(50, $stats['occupancy']['persentase_terisi']);
    }

    #[Test]
    public function get_mitra_stats_returns_summary()
    {
        $kamarRepo = Mockery::mock(KamarRepositoryInterface::class);
        $pembayaranRepo = Mockery::mock(PembayaranRepositoryInterface::class);
        $penghuniKamarRepo = Mockery::mock(PenghuniKamarRepositoryInterface::class);
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        // Return Eloquent\Collection
        $kamarRepo->shouldReceive('all')->once()->andReturn(new EloquentCollection([
            (object)['kos' => (object)['mitra_id' => 1], 'status' => 'terisi'],
            (object)['kos' => (object)['mitra_id' => 1], 'status' => 'kosong'],
            (object)['kos' => (object)['mitra_id' => 2], 'status' => 'terisi'],
        ]));

        $service = new DashboardService($kamarRepo, $pembayaranRepo, $penghuniKamarRepo, $userRepo);
        $stats = $service->getMitraStats(1);

        $this->assertArrayHasKey('total_kamar', $stats);
        $this->assertArrayHasKey('terisi', $stats);
        $this->assertArrayHasKey('kosong', $stats);
    }
}
