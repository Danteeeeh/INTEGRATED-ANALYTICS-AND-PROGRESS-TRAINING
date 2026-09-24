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
        Schema::table('course_completions', function (Blueprint $table) {
            $table->foreign('certificate_id')->references('id')->on('certificates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_completions', function (Blueprint $table) {
            $table->dropForeign(['certificate_id']);
        });
    }
};
