<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->integer('width')->nullable()->after('extension');
            $table->integer('height')->nullable()->after('width');
            $table->string('thumbnail_url')->nullable()->after('height');
            $table->string('duration')->nullable()->after('thumbnail_url');
        });
    }

    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'thumbnail_url', 'duration']);
        });
    }
};
