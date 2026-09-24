<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained('competencies')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->decimal('weight', 5, 2)->default(1);
            $table->timestamps();

            $table->unique(['competency_id', 'course_id', 'module_id', 'lesson_id'], 'course_competency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_competencies');
    }
};
