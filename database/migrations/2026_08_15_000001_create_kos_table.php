<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_id')->constrained('users')->onDelete('cascade');
            $table->string('nama', 100);
            $table->string('slug', 150)->nullable();
            $table->string('foto', 255)->nullable();
            $table->text('alamat')->nullable();
            $table->text('link_gmaps')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('no_rekening', 50)->nullable();
            $table->string('bank', 50)->nullable();
            $table->string('nama_pemilik_rekening', 100)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kos');
    }
};
