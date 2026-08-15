<?php

namespace Tests\Unit\Repositories;

use App\Repositories\Contracts\KamarRepositoryInterface;
use App\Repositories\KamarRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KamarRepositoryTest extends TestCase
{
    #[Test]
    public function repository_implements_interface()
    {
        $repo = new KamarRepository();

        $this->assertInstanceOf(KamarRepositoryInterface::class, $repo);
    }

    #[Test]
    public function repository_has_occupancy_stats_method()
    {
        $repo = new KamarRepository();

        $this->assertTrue(method_exists($repo, 'getOccupancyStats'));
    }

    #[Test]
    public function repository_has_update_status_method()
    {
        $repo = new KamarRepository();

        $this->assertTrue(method_exists($repo, 'updateStatus'));
    }
}
