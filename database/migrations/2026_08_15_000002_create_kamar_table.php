<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kamar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kos_id')->constrained('kos')->onDelete('cascade');
            $table->string('kode_kamar', 20);
            $table->enum('tipe', ['standar', 'berbagi'])->default('standar');
            $table->text('detail')->nullable();
            $table->json('foto')->nullable();
            $table->decimal('harga_per_hari', 12, 2)->nullable();
            $table->decimal('harga_per_minggu', 12, 2)->nullable();
            $table->decimal('harga_per_bulan', 12, 2);
            $table->integer('kapasitas')->default(1);
            $table->enum('status', ['kosong', 'terisi'])->default('kosong');
            $table->string('wa_group_id')->nullable();
            $table->string('link_grup_wa')->nullable();
            $table->datetime('notif_jatuh_tempo_sent_at')->nullable();
            $table->datetime('notif_h7_sent_at')->nullable();
            $table->datetime('notif_h3_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kamar');
    }
};
