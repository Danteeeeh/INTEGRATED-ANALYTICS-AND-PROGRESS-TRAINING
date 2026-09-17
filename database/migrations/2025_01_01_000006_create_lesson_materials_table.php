<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_required')->default(true);
            $table->datetime('access_until')->nullable();
            $table->timestamps();

            $table->index(['lesson_id', 'position']);
            $table->unique(['lesson_id', 'media_file_id'], 'lesson_material_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_materials');
    }
};
