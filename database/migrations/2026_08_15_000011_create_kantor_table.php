<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kantor', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->text('alamat')->nullable();
            $table->decimal('latitude', 30, 8)->nullable();
            $table->decimal('longitude', 30, 8)->nullable();
            $table->string('no_telp', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert initial data kantor untuk Admin & Super Admin
        DB::table('kantor')->insert([
            [
                'nama' => 'Kantor Pusat Kosly (Surabaya)',
                'alamat' => 'Jl. Pemuda No. 45, Surabaya, Jawa Timur',
                'latitude' => -7.250445,
                'longitude' => 112.768845,
                'no_telp' => '031-1234567',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Kantor Cabang Admin Timur',
                'alamat' => 'Jl. Raya Gubeng No. 88, Surabaya, Jawa Timur',
                'latitude' => -7.280000,
                'longitude' => 112.790000,
                'no_telp' => '031-7654321',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Kantor Cabang Admin Barat',
                'alamat' => 'Jl. HR Muhammad No. 120, Surabaya, Jawa Timur',
                'latitude' => -7.260000,
                'longitude' => 112.730000,
                'no_telp' => '031-9876543',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kantor');
    }
};
