<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kos', function (Blueprint $table) {
            $table->string('wa_group_id')->nullable()->after('nama_pemilik_rekening');
            $table->string('link_grup_wa')->nullable()->after('wa_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('kos', function (Blueprint $table) {
            $table->dropColumn(['wa_group_id', 'link_grup_wa']);
        });
    }
};
