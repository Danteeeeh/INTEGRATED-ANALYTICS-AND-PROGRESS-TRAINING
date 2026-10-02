<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track which lesson materials each student has actually accessed,
     * so the "require all materials" completion rule can be enforced.
     */
    public function up(): void
    {
        Schema::create('lesson_material_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('lesson_materials')->cascadeOnDelete();
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['material_id', 'student_id']);
            $table->index(['lesson_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_material_progress');
    }
};
