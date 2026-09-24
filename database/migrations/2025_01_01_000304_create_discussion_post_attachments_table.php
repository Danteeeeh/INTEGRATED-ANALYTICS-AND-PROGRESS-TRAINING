<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_post_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_post_id')->constrained('discussion_posts')->cascadeOnDelete();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['discussion_post_id', 'media_file_id'], 'discussion_post_attachment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_post_attachments');
    }
};
