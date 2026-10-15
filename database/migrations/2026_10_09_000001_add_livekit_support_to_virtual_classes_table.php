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
        // without repeatedly rebuilding this column. This project uses MySQL,
        // but DB_CONNECTION=mariadb reports the driver name "mariadb", so both
        // names have to be treated as the native path.
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE virtual_classes MODIFY meeting_provider VARCHAR(32) NOT NULL DEFAULT 'other'");
        } else {
            // SQLite enforces enum() through a CHECK constraint and PostgreSQL
            // through its own enum type, so the column is rebuilt as a plain string.
            Schema::table('virtual_classes', function (Blueprint $table) {
                $table->string('meeting_provider', 32)->default('other')->change();
            });
        }
    }

    public function down(): void
    {
        DB::table('virtual_classes')->where('meeting_provider', 'livekit')->update([
            'meeting_provider' => 'other',
        ]);

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE virtual_classes MODIFY meeting_provider ENUM('zoom','google_meet','microsoft_teams','other') NOT NULL DEFAULT 'other'");
        } else {
            Schema::table('virtual_classes', function (Blueprint $table) {
                $table->enum('meeting_provider', ['zoom', 'google_meet', 'microsoft_teams', 'other'])->default('other')->change();
            });
        }

        Schema::table('virtual_classes', function (Blueprint $table) {
            $table->dropUnique(['livekit_room_name']);
            $table->dropColumn('livekit_room_name');
        });
    }
};
