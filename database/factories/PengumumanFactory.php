<?php

namespace Database\Factories;

use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengumumanFactory extends Factory
{
    protected $model = Pengumuman::class;

    public function definition(): array
    {
        return [
            'judul' => fake()->sentence(4),
            'isi' => fake()->paragraph(3),
            'tipe' => fake()->randomElement(['pembayaran', 'aturan', 'info']),
            'dibuat_oleh' => User::factory()->admin(),
        ];
    }
}
