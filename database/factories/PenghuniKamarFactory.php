<?php

namespace Database\Factories;

use App\Models\Kamar;
use App\Models\PenghuniKamar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenghuniKamarFactory extends Factory
{
    protected $model = PenghuniKamar::class;

    public function definition(): array
    {
        $durasi = fake()->randomElement(['harian', 'bulanan']);
        $tanggalMasuk = fake()->dateTimeBetween('-6 months', 'now');

        $tanggalKeluar = $durasi === 'harian'
            ? (clone $tanggalMasuk)->modify('+' . fake()->numberBetween(1, 14) . ' days')
            : (clone $tanggalMasuk)->modify('+' . fake()->numberBetween(1, 6) . ' months');

        return [
            'kamar_id' => Kamar::factory(),
            'penghuni_id' => User::factory()->penghuni(),
            'tanggal_masuk' => $tanggalMasuk,
            'tanggal_keluar' => $tanggalKeluar,
            'durasi' => $durasi,
            'status' => fake()->randomElement(['aktif', 'selesai']),
        ];
    }
}
