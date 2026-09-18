<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_item_id')->constrained('grade_items')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('points', 8, 2)->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->string('letter_grade')->nullable();
            $table->text('override_note')->nullable();
            $table->boolean('is_override')->default(false);
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('graded_at')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['grade_item_id', 'student_id'], 'grade_unique');
            $table->index(['student_id', 'graded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
