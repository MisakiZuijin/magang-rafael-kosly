<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kos', function (Blueprint $table) {
            if (Schema::hasColumn('kos', 'wa_group_id')) {
                $table->dropColumn(['wa_group_id', 'link_grup_wa']);
            }
        });

        Schema::table('kamar', function (Blueprint $table) {
            if (!Schema::hasColumn('kamar', 'wa_group_id')) {
                $table->string('wa_group_id')->nullable()->after('kapasitas');
            }
            if (!Schema::hasColumn('kamar', 'link_grup_wa')) {
                $table->string('link_grup_wa')->nullable()->after('wa_group_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kamar', function (Blueprint $table) {
            if (Schema::hasColumn('kamar', 'wa_group_id')) {
                $table->dropColumn(['wa_group_id', 'link_grup_wa']);
            }
        });

        Schema::table('kos', function (Blueprint $table) {
            if (!Schema::hasColumn('kos', 'wa_group_id')) {
                $table->string('wa_group_id')->nullable()->after('nama_pemilik_rekening');
            }
            if (!Schema::hasColumn('kos', 'link_grup_wa')) {
                $table->string('link_grup_wa')->nullable()->after('wa_group_id');
            }
        });
    }
};
