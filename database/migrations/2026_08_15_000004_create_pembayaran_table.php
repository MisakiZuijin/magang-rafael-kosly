<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penghuni_kamar_id')->constrained('penghuni_kamar')->onDelete('cascade');
            $table->decimal('jumlah', 12, 2);
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->enum('status', ['pending', 'terverifikasi', 'ditolak'])->default('pending');
            $table->string('bukti_transfer_url', 255)->nullable();
            $table->date('tanggal_bayar')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
