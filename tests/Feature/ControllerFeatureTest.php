<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // Bypass sanctum untuk test controller logic
        $this->withoutMiddleware(['auth:sanctum']);
    }

    #[Test]
    public function admin_dashboard_returns_json()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'occupancy',
                     'payments',
                     'expiring_soon',
                     'total_users',
                 ]);
    }

    #[Test]
    public function mitra_dashboard_returns_kamar_summary()
    {
        $mitra = User::where('role', 'mitra')->first();

        $response = $this->actingAs($mitra)->getJson('/api/mitra/dashboard');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'kos_list',
                     'kamar_summary',
                     'kamars',
                 ]);
    }

    #[Test]
    public function penghuni_dashboard_returns_stats()
    {
        $penghuni = User::where('role', 'penghuni')->first();

        $response = $this->actingAs($penghuni)->getJson('/api/penghuni/dashboard');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'stats',
                     'show_popup',
                     'aturan',
                 ]);
    }

    #[Test]
    public function universal_dashboard_detects_role()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->getJson('/api/dashboard');
        $response->assertStatus(200)
                 ->assertJsonStructure(['role', 'data']);
    }

    #[Test]
    public function admin_can_list_users_by_role()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->getJson('/api/admin/users?role=mitra');
        $response->assertStatus(200)
                 ->assertJsonCount(3); // 3 mitra dari seeder
    }

    #[Test]
    public function admin_can_list_pending_payments()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->getJson('/api/admin/pembayaran/pending');
        $response->assertStatus(200);
    }

    #[Test]
    public function map_endpoint_returns_locations()
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->getJson('/api/admin/map');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'nama', 'alamat', 'lat', 'lng'],
                 ]);
    }
}
