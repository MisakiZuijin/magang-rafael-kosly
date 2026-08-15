<?php

namespace Database\Factories;

use App\Models\Pembayaran;
use App\Models\PenghuniKamar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PembayaranFactory extends Factory
{
    protected $model = Pembayaran::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'terverifikasi', 'ditolak']);
        $tanggalBayar = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'penghuni_kamar_id' => PenghuniKamar::factory(),
            'jumlah' => fake()->randomElement([800000, 1000000, 1200000, 1500000]),
            'periode_mulai' => $tanggalBayar,
            'periode_selesai' => (clone $tanggalBayar)->modify('+1 month'),
            'status' => $status,
            'bukti_transfer_url' => $status !== 'pending' ? fake()->imageUrl() : null,
            'tanggal_bayar' => $status !== 'pending' ? $tanggalBayar : null,
            'diverifikasi_oleh' => $status === 'terverifikasi' ? User::factory()->admin() : null,
            'tanggal_verifikasi' => $status === 'terverifikasi' ? now() : null,
            'catatan_verifikasi' => $status === 'ditolak' ? fake()->sentence() : null,
        ];
    }
}
