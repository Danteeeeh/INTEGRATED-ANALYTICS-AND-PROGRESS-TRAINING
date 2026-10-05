<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->time('allowed_start_time')->nullable()->after('availability_until');
            $table->time('allowed_end_time')->nullable()->after('allowed_start_time');
            $table->string('video_url')->nullable()->after('allowed_end_time');
            $table->integer('video_duration_minutes')->nullable()->after('video_url');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->time('allowed_start_time')->nullable()->after('ends_at');
            $table->time('allowed_end_time')->nullable()->after('allowed_start_time');
            $table->string('video_url')->nullable()->after('allowed_end_time');
            $table->integer('video_duration_minutes')->nullable()->after('video_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['allowed_start_time', 'allowed_end_time', 'video_url', 'video_duration_minutes']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['allowed_start_time', 'allowed_end_time', 'video_url', 'video_duration_minutes']);
        });
    }
};
