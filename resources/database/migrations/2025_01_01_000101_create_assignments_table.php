<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('instructions')->nullable();
            $table->integer('points')->unsigned()->default(100);
            $table->enum('submission_type', ['text', 'file', 'multiple_files'])->default('file');
            $table->dateTime('due_date')->nullable();
            $table->boolean('allow_late')->default(true);
            $table->integer('late_submission_deduction_percent')->unsigned()->default(0);
            $table->integer('max_attempts')->unsigned()->default(1);
            $table->boolean('allow_resubmission')->default(false);
            $table->dateTime('resubmission_deadline')->nullable();
            $table->dateTime('availability_from')->nullable();
            $table->dateTime('availability_until')->nullable();
            $table->foreignId('rubric_id')->nullable();
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_id', 'status']);
            $table->index('due_date');
            $table->index(['module_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
