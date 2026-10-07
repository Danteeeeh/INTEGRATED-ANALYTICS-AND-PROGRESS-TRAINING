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
        Schema::create('enrollment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->onDelete('set null');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action')->default('auto_enroll');
            $table->text('details')->nullable();
            $table->json('filters')->nullable();
            $table->string('status')->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_logs');
    }
};
