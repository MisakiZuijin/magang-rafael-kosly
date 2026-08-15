<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Kos;
use App\Models\Pembayaran;
use App\Models\PenghuniKamar;
use App\Models\User;
use App\Repositories\Contracts\KamarRepositoryInterface;
use App\Repositories\Contracts\PembayaranRepositoryInterface;
use App\Repositories\Contracts\PenghuniKamarRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepositoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\UserSeeder::class);
        $this->seed(\Database\Seeders\KosSeeder::class);
        $this->seed(\Database\Seeders\KamarSeeder::class);
        $this->seed(\Database\Seeders\PenghuniKamarSeeder::class);
        $this->seed(\Database\Seeders\PembayaranSeeder::class);
    }

    #[Test]
    public function user_repository_finds_user_by_email()
    {
        $repo = app(UserRepositoryInterface::class);
        $user = $repo->findByEmail('superadmin@kos.id');

        $this->assertNotNull($user);
        $this->assertEquals('super_admin', $user->role);
    }

    #[Test]
    public function user_repository_filters_by_role()
    {
        $repo = app(UserRepositoryInterface::class);
        $mitras = $repo->getByRole('mitra');
        $penghunis = $repo->getByRole('penghuni');

        $this->assertGreaterThanOrEqual(3, $mitras->count());
        $this->assertGreaterThanOrEqual(10, $penghunis->count());
    }

    #[Test]
    public function user_repository_toggles_active_status()
    {
        $repo = app(UserRepositoryInterface::class);
        $user = User::where('role', 'penghuni')->first();
        $original = $user->is_active;

        $repo->toggleActive($user->id);
        $user->refresh();

        $this->assertNotEquals($original, $user->is_active);
    }

    #[Test]
    public function kamar_repository_returns_occupancy_stats()
    {
        $repo = app(KamarRepositoryInterface::class);
        $stats = $repo->getOccupancyStats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('terisi', $stats);
        $this->assertArrayHasKey('kosong', $stats);
        $this->assertArrayHasKey('persentase_terisi', $stats);
        $this->assertEquals($stats['total'], $stats['terisi'] + $stats['kosong']);
    }

    #[Test]
    public function kamar_repository_updates_status()
    {
        $repo = app(KamarRepositoryInterface::class);
        $kamar = Kamar::first();

        $repo->updateStatus($kamar->id, 'terisi');
        $kamar->refresh();

        $this->assertEquals('terisi', $kamar->status);
    }

    #[Test]
    public function pembayaran_repository_verifies_payment()
    {
        $repo = app(PembayaranRepositoryInterface::class);
        $admin = User::where('role', 'admin')->first();
        $pembayaran = Pembayaran::where('status', 'pending')->first();

        $this->assertNotNull($pembayaran);

        $result = $repo->verify($pembayaran->id, $admin->id, 'Bukti valid');
        $pembayaran->refresh();

        $this->assertTrue($result);
        $this->assertEquals('terverifikasi', $pembayaran->status);
        $this->assertEquals($admin->id, $pembayaran->diverifikasi_oleh);
        $this->assertNotNull($pembayaran->tanggal_verifikasi);
    }

    #[Test]
    public function pembayaran_repository_rejects_payment()
    {
        $repo = app(PembayaranRepositoryInterface::class);
        $admin = User::where('role', 'admin')->first();
        $pembayaran = Pembayaran::where('status', 'pending')->first();

        $result = $repo->reject($pembayaran->id, $admin->id, 'Bukti tidak jelas');
        $pembayaran->refresh();

        $this->assertTrue($result);
        $this->assertEquals('ditolak', $pembayaran->status);
        $this->assertEquals('Bukti tidak jelas', $pembayaran->catatan_verifikasi);
    }

    #[Test]
    public function penghuni_kamar_repository_checks_out()
    {
        $repo = app(PenghuniKamarRepositoryInterface::class);
        $pk = PenghuniKamar::where('status', 'aktif')->first();

        $this->assertNotNull($pk);

        $kamarId = $pk->kamar_id;
        $result = $repo->checkOut($pk->id);
        $pk->refresh();

        $this->assertTrue($result);
        $this->assertEquals('selesai', $pk->status);

        $kamar = Kamar::find($kamarId);
        $this->assertEquals('kosong', $kamar->status);
    }

    #[Test]
    public function penghuni_kamar_repository_finds_expiring_soon()
    {
        $repo = app(PenghuniKamarRepositoryInterface::class);
        $expiring = $repo->getExpiringSoon(30);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $expiring);
    }
}
