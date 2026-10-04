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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            
            // Exam type: midterm, final, comprehensive, etc.
            $table->enum('exam_type', ['midterm', 'final', 'comprehensive', 'module', 'other'])->default('module');
            
            // Relations
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null');
            $table->foreignId('module_id')->nullable()->constrained('modules')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Exam settings - stricter than quizzes
            $table->integer('duration_minutes')->default(60); // Longer default (1 hour)
            $table->decimal('total_points', 8, 2)->default(100);
            $table->decimal('passing_score_percent', 5, 2)->default(60);
            $table->integer('grade_weight')->default(30); // Grade weight percentage
            
            // Attempt settings - exams typically single attempt
            $table->integer('attempt_limit')->default(1);
            $table->boolean('allow_review')->default(false); // No review by default
            
            // Proctoring settings
            $table->boolean('requires_proctoring')->default(false);
            $table->string('proctoring_method')->nullable(); // 'in_person', 'online', 'ai_proctor', 'hybrid'
            $table->text('proctoring_instructions')->nullable();
            $table->boolean('record_session')->default(false);
            $table->boolean('detect_tab_switch')->default(false);
            $table->boolean('detect_copy_paste')->default(false);
            
            // Navigation and security
            $table->boolean('allow_navigation')->default(false); // No navigation by default
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_choices')->default(true);
            $table->boolean('show_question_number')->default(true);
            $table->boolean('show_timer')->default(true);
            
            // Auto-save and submit
            $table->integer('auto_save_seconds')->default(30);
            $table->boolean('auto_submit_on_timeout')->default(true);
            
            // Result visibility - stricter for exams
            $table->enum('result_visibility', ['immediately', 'after_grading', 'after_all_submissions', 'after_date', 'never'])->default('after_grading');
            $table->boolean('show_correct_answers')->default(false);
            $table->boolean('show_score')->default(false);
            $table->dateTime('results_release_date')->nullable();
            
            // Availability
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('require_confirmation')->default(true); // Must confirm before starting
            
            // Status
            $table->enum('status', ['draft', 'published', 'closed', 'archived'])->default('draft');
            $table->string('slug')->unique();
            
            // Metadata
            $table->json('settings')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['class_id', 'status']);
            $table->index(['course_id', 'status']);
            $table->index(['starts_at', 'ends_at']);
            $table->index('exam_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
