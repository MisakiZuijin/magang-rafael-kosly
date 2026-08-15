<?php

namespace Database\Factories;

use App\Models\Kos;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KosFactory extends Factory
{
    protected $model = Kos::class;

    public function definition(): array
    {
        return [
            'mitra_id' => User::factory()->mitra(),
            'nama' => 'Kos ' . fake()->streetName(),
            'alamat' => fake()->address(),
            'latitude' => fake()->latitude(-6.3, -6.1),
            'longitude' => fake()->longitude(106.7, 106.9),
            'deskripsi' => fake()->paragraph(),
            'no_rekening' => fake()->numerify('################'),
            'bank' => fake()->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI', 'BSI']),
            'nama_pemilik_rekening' => fake()->name(),
        ];
    }
}
