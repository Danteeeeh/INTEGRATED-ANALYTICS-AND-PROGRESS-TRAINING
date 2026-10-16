<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instructor grade-extraction submissions.
 *
 * This is the LMS-side record of "the instructor extracted and submitted the
 * LMS assessment scores for this class". It is deliberately NOT an official
 * academic record: Academic Affairs (AA) remains the source of truth for final
 * grades, and nothing here writes to `enrollments.final_grade` or to any AA
 * table. The row is a snapshot plus an audit trail, so the Super Admin can
 * monitor submissions and AA Staff can read their status.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grade_submissions')) {
            return;
        }

        Schema::create('grade_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();

            // The instructor who owns the class, kept separately from
            // submitted_by so a delegated submission is still attributable to
            // the class owner.
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();

            // previewed | submitted | failed
            $table->string('status')->default('submitted');
            $table->string('period_label')->nullable();

            $table->unsignedInteger('student_count')->default(0);
            $table->unsignedInteger('graded_count')->default(0);
            $table->unsignedInteger('missing_count')->default(0);
            $table->decimal('average_percent', 5, 2)->nullable();

            // The full extracted snapshot (rows + summary) as validated at
            // submission time.
            $table->json('payload')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['class_id', 'status']);
            $table->index('instructor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_submissions');
    }
};
