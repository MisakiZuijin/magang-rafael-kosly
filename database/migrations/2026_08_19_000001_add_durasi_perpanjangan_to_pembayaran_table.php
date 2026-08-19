<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->enum('tipe_perpanjangan', ['bulanan', 'harian'])->default('bulanan')->after('jumlah');
            $table->integer('jumlah_hari')->default(30)->after('tipe_perpanjangan');
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn(['tipe_perpanjangan', 'jumlah_hari']);
        });
    }
};
