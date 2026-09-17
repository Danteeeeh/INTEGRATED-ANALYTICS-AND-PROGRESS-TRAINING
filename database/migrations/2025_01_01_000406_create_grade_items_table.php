<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_category_id')->constrained('grade_categories')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('max_points', 8, 2)->default(100);
            $table->decimal('factor', 5, 2)->default(1);
            $table->enum('item_type', ['assignment', 'quiz', 'project', 'exam', 'participation', 'attendance', 'other'])->default('assignment');
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_released')->default(false);
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['grade_category_id', 'position']);
            $table->index(['class_id', 'due_date']);
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_items');
    }
};
