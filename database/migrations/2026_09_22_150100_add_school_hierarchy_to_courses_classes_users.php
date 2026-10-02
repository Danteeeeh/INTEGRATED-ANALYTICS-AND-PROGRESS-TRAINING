<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('academic_period_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->after('department_id')->constrained('programs')->nullOnDelete();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('academic_period_id')->constrained('sections')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('role_id')->constrained('sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
