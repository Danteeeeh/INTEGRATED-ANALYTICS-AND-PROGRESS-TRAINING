<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('progress_id')->nullable()->constrained('course_progress')->nullOnDelete();
            $table->dateTime('completed_at');
            $table->decimal('completion_percent', 5, 2)->default(0);
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->json('requirements_met')->nullable();
            $table->foreignId('certificate_id')->nullable();
            $table->timestamps();

            $table->unique(['class_id', 'student_id'], 'course_completion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_completions');
    }
};
