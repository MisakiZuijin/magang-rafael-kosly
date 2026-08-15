<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MiddlewareFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // Bypass sanctum, kita mau test RoleMiddleware saja
        $this->withoutMiddleware(['auth:sanctum']);
    }

    #[Test]
    public function penghuni_cannot_access_admin_routes()
    {
        $penghuni = User::factory()->penghuni()->create();

        $response = $this->actingAs($penghuni)->getJson('/api/admin/dashboard');
        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_access_admin_routes()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');
        $response->assertStatus(200);
    }

    #[Test]
    public function super_admin_can_access_all_routes()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->getJson('/api/admin/dashboard');
        $response->assertStatus(200);

        $response2 = $this->actingAs($superAdmin)->getJson('/api/penghuni/dashboard');
        $response2->assertStatus(200);
    }

    #[Test]
    public function mitra_can_access_mitra_routes()
    {
        $mitra = User::factory()->mitra()->create();

        $response = $this->actingAs($mitra)->getJson('/api/mitra/dashboard');
        $response->assertStatus(200);
    }

    #[Test]
    public function guest_gets_401_on_protected_routes()
    {
        // Tanpa actingAs, request tanpa user
        $response = $this->getJson('/api/admin/dashboard');
        $response->assertStatus(401);
    }
}
