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
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            
            // Student's answer
            $table->text('answer_text')->nullable();
            $table->json('answer_data')->nullable(); // For complex answers
            
            // Grading
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_awarded', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->text('grader_notes')->nullable();
            
            // Timing
            $table->timestamp('answered_at')->nullable();
            $table->integer('time_spent_seconds')->default(0);
            
            // Flagged for review
            $table->boolean('flagged_for_review')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['exam_attempt_id', 'question_id']);
            $table->index(['exam_attempt_id', 'is_correct']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
