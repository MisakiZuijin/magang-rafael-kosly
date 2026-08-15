<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumuman_target', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengumuman_id')->constrained('pengumuman')->onDelete('cascade');
            $table->enum('target_tipe', ['kos', 'kamar']);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman_target');
    }
};
