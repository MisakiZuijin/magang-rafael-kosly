<?php

namespace Database\Factories;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LogAktivitasFactory extends Factory
{
    protected $model = LogAktivitas::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'aksi' => fake()->randomElement(['login', 'logout', 'create', 'update', 'delete', 'verify_payment', 'send_announcement']),
            'detail' => fake()->sentence(),
        ];
    }
}
