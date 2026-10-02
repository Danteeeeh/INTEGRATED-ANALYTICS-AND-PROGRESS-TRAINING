<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the redundant CourseCategory grouping. Courses are already
     * organized by Department + Program, so the parallel category tree was a
     * duplicate that confused the UI. This drops the course_categories table
     * and the courses.category_id foreign key.
     */
    public function up(): void
    {
        // Drop the FK + column on courses first (courses.category_id).
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }
        });

        Schema::dropIfExists('course_categories');
    }

    /**
     * Recreate the table + column in case the removal must be rolled back.
     * (Data from course_categories is NOT restored by a rollback — the table
     * comes back empty. Keep the SQL dump taken before this migration for any
     * data restore.)
     */
    public function down(): void
    {
        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('course_categories')->nullOnDelete();
            $table->string('thumbnail')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('academic_period_id')->constrained('course_categories')->nullOnDelete();
            }
        });
    }
};
