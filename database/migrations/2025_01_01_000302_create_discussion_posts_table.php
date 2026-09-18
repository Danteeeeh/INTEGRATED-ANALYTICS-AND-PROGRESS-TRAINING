<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->constrained('discussions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('discussion_posts')->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->longText('body');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->unsignedInteger('reported_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['discussion_id', 'created_at']);
            $table->index('parent_id');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_posts');
    }
};
