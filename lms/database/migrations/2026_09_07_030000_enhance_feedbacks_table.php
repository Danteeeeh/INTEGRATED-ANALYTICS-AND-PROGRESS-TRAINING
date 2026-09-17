<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->integer('rating')->nullable()->after('is_private');
            $table->string('feedback_type')->nullable()->after('rating');
            $table->json('tags')->nullable()->after('feedback_type');
            $table->timestamp('read_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['rating', 'feedback_type', 'tags', 'read_at']);
        });
    }
};
