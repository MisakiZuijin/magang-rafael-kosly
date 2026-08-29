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
        $samplePhotos = [
            'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=800&q=80',
        ];

        return [
            'mitra_id' => User::factory()->mitra(),
            'nama' => 'Kos ' . fake()->streetName(),
            'foto' => fake()->randomElement($samplePhotos),
            'alamat' => fake()->address(),
            'link_gmaps' => 'https://maps.google.com/?q=' . fake()->latitude(-6.3, -6.1) . ',' . fake()->longitude(106.7, 106.9),
            'deskripsi' => fake()->paragraph(),
            'no_rekening' => fake()->numerify('################'),
            'bank' => fake()->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI', 'BSI']),
            'nama_pemilik_rekening' => fake()->name(),
        ];
    }
}
