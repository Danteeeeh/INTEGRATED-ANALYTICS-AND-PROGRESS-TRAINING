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
        Schema::create('section_module_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['section_id', 'module_id', 'course_id'], 'section_module_unique');
            $table->index(['section_id', 'status']);
            $table->index(['module_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_module_assignments');
    }
};
