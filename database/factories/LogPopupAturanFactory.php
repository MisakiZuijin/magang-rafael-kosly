<?php

namespace Database\Factories;

use App\Models\Kos;
use App\Models\LogPopupAturan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LogPopupAturanFactory extends Factory
{
    protected $model = LogPopupAturan::class;

    public function definition(): array
    {
        return [
            'penghuni_id' => User::factory()->penghuni(),
            'kos_id' => Kos::factory(),
            'tanggal_popup' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
