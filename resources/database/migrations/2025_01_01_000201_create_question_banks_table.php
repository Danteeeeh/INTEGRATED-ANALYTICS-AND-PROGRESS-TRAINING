<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->boolean('is_shared')->default(false);
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'status']);
            $table->index(['class_id', 'status']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
