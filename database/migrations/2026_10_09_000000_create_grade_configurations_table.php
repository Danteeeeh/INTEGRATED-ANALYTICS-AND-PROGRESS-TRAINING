<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instructor-defined grading configuration, one row per class block.
 *
 * The configuration holds the *percentage weight* of each grade component
 * (quiz / assignment / exam / attendance / …). Individual GradeItems keep their
 * `factor` as the weight *within* their component, so adding a second quiz
 * never changes how much the exam is worth.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grade_configurations')) {
            return;
        }

        Schema::create('grade_configurations', function (Blueprint $table) {
            $table->id();

            // One configuration per subject offering.
            $table->foreignId('class_id')->unique()->constrained('classes')->cascadeOnDelete();

            // Every component is opt-in. Attendance in particular must never be
            // silently worth 15% just because a template said so.
            $table->decimal('quiz_weight', 5, 2)->default(0);
            $table->decimal('assignment_weight', 5, 2)->default(0);
            $table->decimal('exam_weight', 5, 2)->default(0);
            $table->decimal('project_weight', 5, 2)->default(0);
            $table->decimal('participation_weight', 5, 2)->default(0);
            $table->decimal('attendance_weight', 5, 2)->default(0);
            $table->decimal('other_weight', 5, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_configurations');
    }
};