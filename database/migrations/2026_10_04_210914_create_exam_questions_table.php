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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            
            // Question order and settings
            $table->integer('order')->default(0);
            $table->decimal('points', 8, 2)->default(1);
            $table->boolean('is_required')->default(true);
            
            // Question pool settings (for random selection)
            $table->integer('pool_size')->nullable();
            $table->boolean('random_from_pool')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['exam_id', 'question_id']);
            $table->index(['exam_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
