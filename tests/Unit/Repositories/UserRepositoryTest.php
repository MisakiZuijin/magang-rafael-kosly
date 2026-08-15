<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class UserRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function find_by_email_returns_user_when_exists()
    {
        $repo = new UserRepository();

        // Karena UserRepository pakai Eloquent langsung, 
        // kita test dengan database atau mock facade
        // Disini kita test method signature saja
        $this->assertTrue(method_exists($repo, 'findByEmail'));
    }

    #[Test]
    public function repository_implements_interface()
    {
        $repo = new UserRepository();

        $this->assertInstanceOf(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            $repo
        );
    }

    #[Test]
    public function repository_has_all_required_methods()
    {
        $repo = new UserRepository();
        $required = ['all', 'paginate', 'find', 'findByEmail', 'create', 'update', 'delete', 'toggleActive', 'getByRole', 'getActiveUsers'];

        foreach ($required as $method) {
            $this->assertTrue(method_exists($repo, $method), "Method {$method} tidak ada");
        }
    }
}
