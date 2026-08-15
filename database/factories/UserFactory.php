<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'no_hp' => fake()->phoneNumber(),
            'role' => fake()->randomElement(['super_admin', 'admin', 'mitra', 'penghuni']),
            'foto_profile' => null,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'super_admin',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function mitra(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'mitra',
        ]);
    }

    public function penghuni(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'penghuni',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
