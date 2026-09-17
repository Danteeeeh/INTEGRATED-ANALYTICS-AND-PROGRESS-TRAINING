<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_answer_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_answer_id')->constrained('quiz_answers')->cascadeOnDelete();
            $table->foreignId('question_choice_id')->constrained('question_choices')->cascadeOnDelete();
            $table->boolean('is_selected')->default(false);
            $table->timestamps();

            $table->unique(['quiz_answer_id', 'question_choice_id'], 'quiz_answer_choice_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answer_choices');
    }
};
