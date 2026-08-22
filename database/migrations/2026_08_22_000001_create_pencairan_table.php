<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pencairan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kos_id')->constrained('kos')->onDelete('cascade');
            $table->foreignId('mitra_id')->constrained('users')->onDelete('cascade');
            $table->integer('bulan');
            $table->integer('tahun');
            $table->decimal('total_pendapatan', 12, 2)->default(0);
            $table->enum('status', ['pending', 'dicairkan'])->default('pending');
            $table->timestamp('tanggal_cair')->nullable();
            $table->string('bukti_transfer', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('dicairkan_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['kos_id', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pencairan');
    }
};
