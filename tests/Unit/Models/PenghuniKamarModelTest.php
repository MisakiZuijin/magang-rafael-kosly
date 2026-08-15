<?php

namespace Tests\Unit\Models;

use App\Models\Kamar;
use App\Models\Kos;
use App\Models\PenghuniKamar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PenghuniKamarModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function penghuni_kamar_has_sisa_hari_accessor()
    {
        $kos = Kos::factory()->create();
        $kamar = Kamar::factory()->create(['kos_id' => $kos->id]);
        $penghuni = User::factory()->penghuni()->create();

        $pk = PenghuniKamar::factory()->create([
            'kamar_id' => $kamar->id,
            'penghuni_id' => $penghuni->id,
            'tanggal_masuk' => now(),
            'tanggal_keluar' => now()->addDays(10),
            'status' => 'aktif',
        ]);

        // diffInDays bisa return float (9.83...), jadi assert dengan delta
        $this->assertEqualsWithDelta(10, $pk->sisa_hari, 1);
    }

    #[Test]
    public function penghuni_kamar_sisa_hari_returns_null_without_tanggal_keluar()
    {
        $pk = new PenghuniKamar();

        $this->assertNull($pk->sisa_hari);
    }

    #[Test]
    public function penghuni_kamar_scope_aktif_filters_correctly()
    {
        $kos = Kos::factory()->create();
        $kamar = Kamar::factory()->create(['kos_id' => $kos->id]);
        $penghuni = User::factory()->penghuni()->create();

        PenghuniKamar::factory()->create([
            'kamar_id' => $kamar->id,
            'penghuni_id' => $penghuni->id,
            'status' => 'aktif',
        ]);
        PenghuniKamar::factory()->create([
            'kamar_id' => $kamar->id,
            'penghuni_id' => User::factory()->penghuni()->create()->id,
            'status' => 'selesai',
        ]);

        $aktifCount = PenghuniKamar::aktif()->count();

        $this->assertEquals(1, $aktifCount);
    }
}
