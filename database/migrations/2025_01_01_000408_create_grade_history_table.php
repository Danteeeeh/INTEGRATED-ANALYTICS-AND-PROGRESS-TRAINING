<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->cascadeOnDelete();
            $table->foreignId('grade_item_id')->constrained('grade_items')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('previous_points', 8, 2)->nullable();
            $table->decimal('new_points', 8, 2)->nullable();
            $table->decimal('previous_percent', 5, 2)->nullable();
            $table->decimal('new_percent', 5, 2)->nullable();
            $table->string('previous_letter')->nullable();
            $table->string('new_letter')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->text('change_reason')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['grade_id', 'changed_at']);
            $table->index(['student_id', 'changed_at']);
            $table->index(['grade_item_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_history');
    }
};
