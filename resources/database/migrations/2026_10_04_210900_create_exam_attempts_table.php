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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Attempt info
            $table->integer('attempt_number')->default(1);
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('time_spent_seconds')->default(0);
            $table->integer('time_remaining_seconds')->nullable();
            
            // Status
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'auto_submitted', 'graded', 'reviewed'])->default('not_started');
            
            // Scores
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->decimal('total_points', 8, 2)->nullable();
            $table->decimal('earned_points', 8, 2)->nullable();
            $table->boolean('is_passed')->nullable();
            
            // Proctoring data
            $table->json('proctoring_log')->nullable();
            $table->integer('tab_switch_count')->default(0);
            $table->integer('suspicious_activity_count')->default(0);
            $table->boolean('flagged_for_review')->default(false);
            $table->text('proctoring_notes')->nullable();
            
            // IP and device tracking
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('browser_fingerprint')->nullable();
            
            // Submission confirmation
            $table->boolean('confirmed_before_start')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            
            // Grading
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->text('grading_notes')->nullable();
            
            // Review status
            $table->boolean('reviewed')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['exam_id', 'student_id']);
            $table->index(['student_id', 'status']);
            $table->index(['exam_id', 'status']);
            $table->index('started_at');
            $table->index('submitted_at');
            $table->unique(['exam_id', 'student_id', 'attempt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
