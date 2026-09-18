<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedInteger('attempt_limit')->default(1);
            $table->unsignedInteger('passing_score_percent')->default(0);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_choices')->default(false);
            $table->boolean('allow_navigation')->default(true);
            $table->unsignedInteger('auto_save_seconds')->nullable()->default(60);
            $table->boolean('auto_submit_on_timeout')->default(true);
            $table->enum('result_visibility', ['always', 'after_grading', 'never'])->default('always');
            $table->boolean('review_allowed')->default(true);
            $table->boolean('show_correct_answers')->default(true);
            $table->dateTime('availability_from')->nullable();
            $table->dateTime('availability_until')->nullable();
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_id', 'status']);
            $table->index('availability_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
