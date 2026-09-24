<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained('competencies')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('current_level')->nullable();
            $table->string('required_level')->nullable();
            $table->unsignedInteger('evidence_count')->default(0);
            $table->dateTime('mastered_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'competency_id', 'class_id'], 'student_competency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_competencies');
    }
};
