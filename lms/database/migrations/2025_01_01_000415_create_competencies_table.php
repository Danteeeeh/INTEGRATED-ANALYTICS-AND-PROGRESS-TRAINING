<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('framework_id')->constrained('competency_frameworks')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('competencies')->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('learning_outcomes')->nullable();
            $table->json('mastery_levels')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['framework_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencies');
    }
};
