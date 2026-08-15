<?php

namespace Database\Factories;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotifikasiFactory extends Factory
{
    protected $model = Notifikasi::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'judul' => fake()->sentence(3),
            'pesan' => fake()->paragraph(2),
            'channel' => fake()->randomElement(['web', 'whatsapp']),
            'status' => fake()->randomElement(['terkirim', 'gagal', 'dibaca']),
        ];
    }
}
