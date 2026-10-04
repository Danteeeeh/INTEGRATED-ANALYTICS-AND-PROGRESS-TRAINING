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
        Schema::create('exam_answer_choices', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('exam_answer_id')->constrained('exam_answers')->onDelete('cascade');
            $table->foreignId('question_choice_id')->constrained('question_choices')->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['exam_answer_id', 'question_choice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answer_choices');
    }
};
