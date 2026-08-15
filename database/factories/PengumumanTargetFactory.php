<?php

namespace Database\Factories;

use App\Models\Kamar;
use App\Models\Kos;
use App\Models\Pengumuman;
use App\Models\PengumumanTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengumumanTargetFactory extends Factory
{
    protected $model = PengumumanTarget::class;

    public function definition(): array
    {
        $targetTipe = fake()->randomElement(['kos', 'kamar']);

        return [
            'pengumuman_id' => Pengumuman::factory(),
            'target_tipe' => $targetTipe,
            'target_id' => $targetTipe === 'kos' ? Kos::factory() : Kamar::factory(),
        ];
    }
}
