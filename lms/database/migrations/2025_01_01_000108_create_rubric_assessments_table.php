<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubric_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            $table->foreignId('rubric_id')->constrained('rubrics')->cascadeOnDelete();
            $table->foreignId('rubric_criterion_id')->constrained('rubric_criteria')->cascadeOnDelete();
            $table->foreignId('rubric_level_id')->nullable()->constrained('rubric_levels')->nullOnDelete();
            $table->decimal('points_awarded', 8, 2)->default(0);
            $table->text('feedback')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assignment_submission_id', 'rubric_criterion_id'], 'rubric_assessment_unique');
            $table->index('rubric_id');
            $table->index('assessed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_assessments');
    }
};
