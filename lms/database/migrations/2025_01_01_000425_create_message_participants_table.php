<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_thread_id')->constrained('message_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_muted')->default(false);
            $table->timestamps();

            $table->unique(['message_thread_id', 'user_id'], 'message_participant_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_participants');
    }
};
