<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subject verification lifecycle.
 *
 * A class block is a "subject offering": a course taught to one section in one
 * academic period. This adds the sign-off state that the registrar's office
 * needs before a grade can be treated as final.
 *
 *   draft    → grades can still change, nothing is committed
 *   verified → frozen; the academic record for the period is finalised
 */
return new class extends Migration
{
    public function up(): void
    {
        // courses.credits already exists (add_credits_to_courses_table) and is
        // what the transcript weights its GWA by, so no units column is needed.
        if (Schema::hasColumn('classes', 'subject_status')) {
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->string('subject_status')->default('draft')->after('status');

            $table->timestamp('subject_verified_at')->nullable()->after('subject_status');

            $table->foreignId('subject_verified_by')
                ->nullable()
                ->after('subject_verified_at')
                ->constrained('users')
                ->nullOnDelete();

            // Every listing filters on (status, subject_status) together.
            $table->index(['subject_status', 'status'], 'classes_subject_status_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('classes', 'subject_status')) {
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex('classes_subject_status_index');

            $table->dropConstrainedForeignId('subject_verified_by');

            $table->dropColumn(['subject_status', 'subject_verified_at']);
        });
    }
};