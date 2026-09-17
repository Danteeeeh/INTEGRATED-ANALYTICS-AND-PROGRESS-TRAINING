<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubrics')->cascadeOnDelete();
            $table->string('criterion');
            $table->text('description')->nullable();
            $table->integer('position')->unsigned()->default(0);
            $table->decimal('max_points', 8, 2)->default(0);
            $table->timestamps();

            $table->index(['rubric_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_criteria');
    }
};
