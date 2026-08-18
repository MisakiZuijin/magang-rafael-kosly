<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pengumuman', 'channel')) {
            Schema::table('pengumuman', function (Blueprint $table) {
                $table->enum('channel', ['web', 'whatsapp', 'keduanya'])->default('web')->after('tipe');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pengumuman', 'channel')) {
            Schema::table('pengumuman', function (Blueprint $table) {
                $table->dropColumn('channel');
            });
        }
    }
};
