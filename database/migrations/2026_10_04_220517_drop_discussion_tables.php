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
        Schema::dropIfExists('discussion_post_attachments');
        Schema::dropIfExists('discussion_posts');
        Schema::dropIfExists('discussion_subscriptions');
        Schema::dropIfExists('discussions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as the original migration files were deleted
    }
};
