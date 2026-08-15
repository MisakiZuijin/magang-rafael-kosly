<?php

namespace Tests\Unit\Models;

use App\Models\Kamar;
use App\Models\Kos;
use App\Models\PenghuniKamar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KamarModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function kamar_belongs_to_kos()
    {
        $kamar = new Kamar();
        $relation = $kamar->kos();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    #[Test]
    public function kamar_has_penghuni_kamars_relation()
    {
        $kamar = new Kamar();
        $relation = $kamar->penghuniKamars();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
    }

    #[Test]
    public function kamar_default_status_is_kosong()
    {
        // Gunakan factory agar default DB terbaca
        $kamar = Kamar::factory()->make();

        $this->assertEquals('kosong', $kamar->status);
    }

    #[Test]
    public function kamar_standart_has_kapasitas_one()
    {
        $kamar = Kamar::factory()->make(['tipe' => 'standar']);

        $this->assertEquals(1, $kamar->kapasitas);
    }

    #[Test]
    public function kamar_berbagi_has_kapasitas_two()
    {
        $kamar = Kamar::factory()->make(['tipe' => 'berbagi', 'kapasitas' => 2]);

        $this->assertEquals(2, $kamar->kapasitas);
    }

    #[Test]
    public function kamar_jumlah_penghuni_accessor_works()
    {
        $kos = Kos::factory()->create();
        $kamar = Kamar::factory()->create([
            'kos_id' => $kos->id,
            'tipe' => 'berbagi',
            'kapasitas' => 2,
            'status' => 'terisi',
        ]);

        $penghuni1 = User::factory()->penghuni()->create();
        $penghuni2 = User::factory()->penghuni()->create();

        PenghuniKamar::factory()->create([
            'kamar_id' => $kamar->id,
            'penghuni_id' => $penghuni1->id,
            'status' => 'aktif',
        ]);
        PenghuniKamar::factory()->create([
            'kamar_id' => $kamar->id,
            'penghuni_id' => $penghuni2->id,
            'status' => 'aktif',
        ]);

        $this->assertEquals(2, $kamar->fresh()->jumlah_penghuni);
    }

    #[Test]
    public function kamar_is_full_when_capacity_reached()
    {
        $kos = Kos::factory()->create();
        $kamar = Kamar::factory()->create([
            'kos_id' => $kos->id,
            'tipe' => 'standar',
            'kapasitas' => 1,
        ]);

        $penghuni = User::factory()->penghuni()->create();
        PenghuniKamar::factory()->create([
            'kamar_id' => $kamar->id,
            'penghuni_id' => $penghuni->id,
            'status' => 'aktif',
        ]);

        $this->assertTrue($kamar->fresh()->is_full);
    }
}
