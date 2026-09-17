<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('link_url')->nullable();
            $table->enum('channel', ['in_app', 'email', 'both'])->default('in_app');
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
