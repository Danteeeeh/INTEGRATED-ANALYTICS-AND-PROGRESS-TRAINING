<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained('question_banks')->cascadeOnDelete();
            $table->enum('question_type', ['multiple_choice', 'multiple_answer', 'true_false', 'identification', 'short_answer', 'essay'])->default('multiple_choice');
            $table->longText('question_text');
            $table->text('explanation')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->decimal('default_points', 8, 2)->default(1);
            $table->json('tags')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['draft', 'active', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['question_bank_id', 'question_type']);
            $table->index('difficulty');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
