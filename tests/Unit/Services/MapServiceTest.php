<?php

namespace Tests\Unit\Services;

use App\Repositories\Contracts\KosRepositoryInterface;
use App\Services\MapService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class MapServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function get_all_locations_returns_formatted_array()
    {
        $kosRepo = Mockery::mock(KosRepositoryInterface::class);
        // Return Eloquent\Collection
        $kosRepo->shouldReceive('getLocations')->once()->andReturn(new EloquentCollection([
            (object)['id' => 1, 'nama' => 'Kos A', 'alamat' => 'Jl. A', 'latitude' => '-6.2', 'longitude' => '106.8'],
            (object)['id' => 2, 'nama' => 'Kos B', 'alamat' => 'Jl. B', 'latitude' => '-6.3', 'longitude' => '106.9'],
        ]));

        $service = new MapService($kosRepo);
        $locations = $service->getAllLocations();

        $this->assertCount(2, $locations);
        $this->assertEquals('Kos A', $locations[0]['nama']);
        $this->assertEquals(-6.2, $locations[0]['lat']);
        $this->assertEquals(106.8, $locations[0]['lng']);
    }

    #[Test]
    public function get_location_by_id_returns_null_when_not_found()
    {
        $kosRepo = Mockery::mock(KosRepositoryInterface::class);
        $kosRepo->shouldReceive('find')->with(999)->once()->andReturn(null);

        $service = new MapService($kosRepo);
        $location = $service->getLocationById(999);

        $this->assertNull($location);
    }
}
