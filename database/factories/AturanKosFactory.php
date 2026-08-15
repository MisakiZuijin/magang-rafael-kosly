<?php

namespace Database\Factories;

use App\Models\AturanKos;
use App\Models\Kos;
use Illuminate\Database\Eloquent\Factories\Factory;

class AturanKosFactory extends Factory
{
    protected $model = AturanKos::class;

    public function definition(): array
    {
        return [
            'kos_id' => Kos::factory(),
            'isi_aturan' => fake()->randomElement([
                'Dilarang merokok di dalam kamar. Jam malam pukul 22:00. Dilarang membawa hewan peliharaan.',
                'Tamu diperbolehkan hingga pukul 21:00. Dilarang membuat keributan setelah jam 22:00.',
                'Pembayaran dilakukan paling lambat tanggal 5 setiap bulannya. Denda keterlambatan Rp 10.000/hari.',
                'Listrik token. Air PAM. Dilarang menggunakan alat elektronik berdaya tinggi.',
            ]),
        ];
    }
}
