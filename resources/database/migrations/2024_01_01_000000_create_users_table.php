<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('identifier')->unique()->nullable()->comment('Student/employee ID');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('profile_photo_path')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['role_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
