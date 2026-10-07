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
        Schema::create('terms_of_service_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terms_of_service_id')->constrained('terms_of_service')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('accepted_at');
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->unique(['terms_of_service_id', 'user_id']);
            $table->index('user_id');
            $table->index('accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_of_service_acceptances');
    }
};
