<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_popup_aturan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penghuni_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('kos_id')->constrained('kos')->onDelete('cascade');
            $table->date('tanggal_popup');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_popup_aturan');
    }
};
