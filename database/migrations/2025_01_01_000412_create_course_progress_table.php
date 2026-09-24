<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('modules_completed')->default(0);
            $table->unsignedInteger('total_modules')->default(0);
            $table->unsignedInteger('lessons_completed')->default(0);
            $table->unsignedInteger('total_lessons')->default(0);
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'dropped'])->default('not_started');
            $table->timestamps();

            $table->unique(['class_id', 'student_id'], 'course_progress_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_progress');
    }
};
