<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->integer('attempt_number')->unsigned()->default(1);
            $table->longText('submission_text')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->enum('status', ['in_progress', 'submitted', 'graded', 'returned', 'resubmitted'])->default('in_progress');
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['assignment_id', 'student_id', 'attempt_number'], 'assignment_submission_unique');
            $table->index(['assignment_id', 'student_id']);
            $table->index('status');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
