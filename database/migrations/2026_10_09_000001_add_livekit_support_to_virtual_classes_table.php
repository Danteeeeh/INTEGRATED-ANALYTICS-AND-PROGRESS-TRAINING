<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_classes', function (Blueprint $table) {
            $table->string('livekit_room_name', 128)->nullable()->unique();
        });

        // Use VARCHAR instead of ENUM so future meeting providers can be added
        // without repeatedly rebuilding this column. This project uses MySQL.
        DB::statement("ALTER TABLE virtual_classes MODIFY meeting_provider VARCHAR(32) NOT NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        DB::table('virtual_classes')->where('meeting_provider', 'livekit')->update([
            'meeting_provider' => 'other',
        ]);

        DB::statement("ALTER TABLE virtual_classes MODIFY meeting_provider ENUM('zoom','google_meet','microsoft_teams','other') NOT NULL DEFAULT 'other'");

        Schema::table('virtual_classes', function (Blueprint $table) {
            $table->dropUnique(['livekit_room_name']);
            $table->dropColumn('livekit_room_name');
        });
    }
};
