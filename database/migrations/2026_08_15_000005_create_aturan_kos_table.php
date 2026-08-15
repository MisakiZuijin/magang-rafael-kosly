<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aturan_kos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kos_id')->constrained('kos')->onDelete('cascade');
            $table->text('isi_aturan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aturan_kos');
    }
};
