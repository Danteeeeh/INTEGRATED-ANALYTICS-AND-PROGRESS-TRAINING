<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the redundant GradeCategory grouping. Grade items are already
     * typed via grade_items.item_type (assignment/quiz/project/exam/...), so
     * the parallel category grouping was a duplicate that confused the UI and
     * had no CRUD surface. This drops the grade_categories table and the
     * grade_items.grade_category_id foreign key.
     */
    public function up(): void
    {
        // Drop the FK + column on grade_items first (grade_items.grade_category_id).
        Schema::table('grade_items', function (Blueprint $table) {
            if (Schema::hasColumn('grade_items', 'grade_category_id')) {
                $table->dropConstrainedForeignId('grade_category_id');
            }
        });

        Schema::dropIfExists('grade_categories');
    }

    /**
     * Recreate the table + column in case the removal must be rolled back.
     * (Data from grade_categories is NOT restored by a rollback — the table
     * comes back empty. Keep the SQL dump taken before this migration for any
     * data restore.)
     */
    public function down(): void
    {
        Schema::create('grade_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight_percent', 5, 2)->default(0);
            $table->unsignedInteger('drop_lowest')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_id', 'position']);
        });

        Schema::table('grade_items', function (Blueprint $table) {
            if (! Schema::hasColumn('grade_items', 'grade_category_id')) {
                $table->foreignId('grade_category_id')->nullable()->after('id')->constrained('grade_categories')->cascadeOnDelete();
                $table->index(['grade_category_id', 'position']);
            }
        });
    }
};
