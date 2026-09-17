<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->decimal('points', 8, 2)->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('pool_size')->nullable();
            $table->timestamps();

            $table->index(['quiz_id', 'position']);
            $table->unique(['quiz_id', 'question_id'], 'quiz_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
