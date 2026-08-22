<?php

namespace Database\Factories;

use App\Models\Kamar;
use App\Models\Kos;
use Illuminate\Database\Eloquent\Factories\Factory;

class KamarFactory extends Factory
{
    protected $model = Kamar::class;

    public function definition(): array
    {
        $tipe = fake()->randomElement(['standar', 'berbagi']);
        $kapasitas = $tipe === 'berbagi' ? 2 : 1;

        return [
            'kos_id' => Kos::factory(),
            'kode_kamar' => strtoupper(fake()->bothify('K-###')),
            'tipe' => $tipe,
            'harga_per_hari' => $tipe === 'standar' ? fake()->randomElement([75000, 100000, 125000]) : null,
            'harga_per_minggu' => fake()->randomElement([250000, 300000, 400000]),
            'harga_per_bulan' => fake()->randomElement([800000, 1000000, 1200000, 1500000]),
            'kapasitas' => $kapasitas,
            'status' => 'kosong',
            'wa_group_id' => null,
            'link_grup_wa' => null,
        ];
    }
}
