<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat akun Super Admin
        $superAdmin = \App\Models\User::factory()->superAdmin()->create([
            'nama' => 'Super Admin',
            'email' => 'superadmin@kos.com',
            'password' => bcrypt('password'),
            'no_hp' => '081234567890',
        ]);

        // 2. Buat akun Admin
        $admin = \App\Models\User::factory()->admin()->create([
            'nama' => 'Admin Kos',
            'email' => 'admin@kos.com',
            'password' => bcrypt('password'),
            'no_hp' => '081234567891',
        ]);

        // 3. Buat Mitra dan Kos mereka
        $mitras = \App\Models\User::factory(3)->mitra()->create();

        $mitras->each(function ($mitra) {
            $kos = \App\Models\Kos::factory()->create([
                'mitra_id' => $mitra->id,
            ]);

            // Buat 5-10 kamar per kos
            $kamars = \App\Models\Kamar::factory(fake()->numberBetween(5, 10))->create([
                'kos_id' => $kos->id,
            ]);

            // Buat aturan kos
            \App\Models\AturanKos::factory(fake()->numberBetween(2, 4))->create([
                'kos_id' => $kos->id,
            ]);

            // Isi beberapa kamar dengan penghuni
            $kamars->random(fake()->numberBetween(2, 4))->each(function ($kamar) use ($kos) {
                $kamar->update(['status' => 'terisi']);

                $jumlahPenghuni = $kamar->tipe === 'berbagi' ? 2 : 1;

                for ($i = 0; $i < $jumlahPenghuni; $i++) {
                    $penghuni = \App\Models\User::factory()->penghuni()->create();

                    $penghuniKamar = \App\Models\PenghuniKamar::factory()->create([
                        'kamar_id' => $kamar->id,
                        'penghuni_id' => $penghuni->id,
                        'status' => 'aktif',
                    ]);

                    // Buat pembayaran untuk penghuni
                    \App\Models\Pembayaran::factory(fake()->numberBetween(1, 3))->create([
                        'penghuni_kamar_id' => $penghuniKamar->id,
                    ]);

                    // Buat notifikasi untuk penghuni
                    \App\Models\Notifikasi::factory(fake()->numberBetween(1, 3))->create([
                        'user_id' => $penghuni->id,
                    ]);
                }
            });
        });

        // 4. Buat pengumuman dari admin
        $pengumumans = \App\Models\Pengumuman::factory(5)->create([
            'dibuat_oleh' => $admin->id,
        ]);

        $pengumumans->each(function ($pengumuman) {
            \App\Models\PengumumanTarget::factory(fake()->numberBetween(1, 3))->create([
                'pengumuman_id' => $pengumuman->id,
            ]);
        });

        // 5. Buat log aktivitas
        \App\Models\LogAktivitas::factory(20)->create();

        // 6. Buat notifikasi untuk super admin dan admin
        \App\Models\Notifikasi::factory(3)->create(['user_id' => $superAdmin->id]);
        \App\Models\Notifikasi::factory(3)->create(['user_id' => $admin->id]);
    }
}
