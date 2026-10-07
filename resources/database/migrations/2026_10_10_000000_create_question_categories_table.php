<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-question topic categories (§7 of the Test Bank spec).
 *
 * question_banks.category is a free-text label on the *bank*, which cannot
 * express "10 Hardware, 10 Software, 15 Networking" inside a single subject.
 * Categories therefore live on the question, scoped to a course so two subjects
 * never share a "Networking" topic by accident.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('question_categories')) {
            Schema::create('question_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();

                // Null = available to every course (a shared topic list).
                $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();

                // Not a unique index: with soft deletes every live row has a NULL
                // deleted_at, and MySQL/SQLite treat NULLs as distinct, so the
                // constraint would silently not apply. Uniqueness is enforced in
                // the controller against live rows instead.
                $table->index(['course_id', 'name']);
            });
        }

        if (Schema::hasTable('questions')) {
            if (! Schema::hasColumn('questions', 'category_id')) {
                Schema::table('questions', function (Blueprint $table) {
                    $table->foreignId('category_id')
                        ->nullable()
                        ->after('question_bank_id')
                        ->constrained('question_categories')
                        ->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('questions', 'is_case_sensitive')) {
                Schema::table('questions', function (Blueprint $table) {
                    // Identification questions need to say whether "CPU" and
                    // "cpu" count as the same answer.
                    $table->boolean('is_case_sensitive')->default(false)->after('status');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('questions')) {
            if (Schema::hasColumn('questions', 'category_id')) {
                Schema::table('questions', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('category_id');
                });
            }

            if (Schema::hasColumn('questions', 'is_case_sensitive')) {
                Schema::table('questions', function (Blueprint $table) {
                    $table->dropColumn('is_case_sensitive');
                });
            }
        }

        Schema::dropIfExists('question_categories');
    }
};
