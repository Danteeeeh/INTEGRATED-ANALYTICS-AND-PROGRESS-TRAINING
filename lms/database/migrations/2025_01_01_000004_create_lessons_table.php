<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->longText('content')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->enum('lesson_type', ['text', 'video', 'audio', 'pdf', 'document', 'presentation', 'external'])->default('text');
            $table->string('external_url')->nullable();
            $table->boolean('is_required')->default(true);
            $table->datetime('availability_from')->nullable();
            $table->datetime('availability_until')->nullable();
            $table->json('completion_rules')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['module_id', 'position']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
