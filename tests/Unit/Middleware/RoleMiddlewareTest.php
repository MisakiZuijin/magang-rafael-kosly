<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddlewareTest extends TestCase
{
    #[Test]
    public function super_admin_can_access_any_role_route()
    {
        $middleware = new RoleMiddleware();
        $user = User::factory()->make(['role' => 'super_admin']);

        $request = Request::create('/api/admin/dashboard');
        $request->setUserResolver(fn() => $user);

        $response = $middleware->handle($request, fn($req) => new Response('OK'), 'admin');

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function unauthorized_user_gets_401()
    {
        $middleware = new RoleMiddleware();

        $request = Request::create('/api/admin/dashboard');
        $request->setUserResolver(fn() => null);

        $response = $middleware->handle($request, fn($req) => new Response('OK'), 'admin');

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function wrong_role_gets_403()
    {
        $middleware = new RoleMiddleware();
        $user = User::factory()->make(['role' => 'penghuni']);

        $request = Request::create('/api/admin/dashboard');
        $request->setUserResolver(fn() => $user);

        $response = $middleware->handle($request, fn($req) => new Response('OK'), 'admin');

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function correct_role_can_access()
    {
        $middleware = new RoleMiddleware();
        $user = User::factory()->make(['role' => 'admin']);

        $request = Request::create('/api/admin/dashboard');
        $request->setUserResolver(fn() => $user);

        $response = $middleware->handle($request, fn($req) => new Response('OK'), 'admin');

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function multiple_roles_are_checked()
    {
        $middleware = new RoleMiddleware();
        $user = User::factory()->make(['role' => 'mitra']);

        $request = Request::create('/api/some-route');
        $request->setUserResolver(fn() => $user);

        $response = $middleware->handle($request, fn($req) => new Response('OK'), 'admin', 'mitra');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
