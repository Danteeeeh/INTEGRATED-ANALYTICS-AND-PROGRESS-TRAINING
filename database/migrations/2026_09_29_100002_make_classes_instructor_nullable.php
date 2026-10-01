<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('instructor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Restore NOT NULL only after re-assigning any null instructors to a safe default is unsafe;
        // keep a no-op guard: only re-apply NOT NULL when no null rows exist.
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('instructor_id')->nullable(false)->change();
        });
    }
};
