<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_has_correct_fillable_attributes()
    {
        $user = new User();
        $expected = ['nama', 'email', 'password', 'no_hp', 'role', 'foto_profile', 'is_active'];

        $this->assertEquals($expected, $user->getFillable());
    }

    #[Test]
    public function user_password_is_hashed()
    {
        $user = User::factory()->make(['password' => 'secret123']);

        $this->assertNotEquals('secret123', $user->password);
        $this->assertTrue(password_verify('secret123', $user->password));
    }

    #[Test]
    public function user_scopes_filter_by_role()
    {
        User::factory()->admin()->create(['email' => 'admin@test.com']);
        User::factory()->penghuni()->create(['email' => 'penghuni@test.com']);

        $admins = User::admin()->get();
        $penghunis = User::penghuni()->get();

        $this->assertCount(1, $admins);
        $this->assertCount(1, $penghunis);
        $this->assertEquals('admin@test.com', $admins->first()->email);
    }

    #[Test]
    public function user_has_kos_relation()
    {
        $user = new User();
        $relation = $user->kos();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }

    #[Test]
    public function user_default_role_is_penghuni()
    {
        $user = new User();

        $this->assertEquals('penghuni', $user->getAttributes()['role'] ?? 'penghuni');
    }
}
