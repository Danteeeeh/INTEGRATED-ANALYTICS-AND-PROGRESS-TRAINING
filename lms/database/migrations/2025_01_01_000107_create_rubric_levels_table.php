<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubric_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_criterion_id')->constrained('rubric_criteria')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('points', 8, 2)->default(0);
            $table->integer('position')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['rubric_criterion_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_levels');
    }
};
