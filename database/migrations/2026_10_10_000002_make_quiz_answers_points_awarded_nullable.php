<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an ungraded answer say so.
 *
 * quiz_answers.points_awarded was `decimal NOT NULL DEFAULT 0`, while
 * exam_answers.points_awarded is nullable. That mismatch made it impossible to
 * hold an essay answer open for the instructor: writing NULL raised a NOT NULL
 * violation, and writing 0 was indistinguishable from "marked wrong".
 *
 * NULL now means "awaiting an instructor" in both tables, and 0 means "marked
 * zero" (§21: instructor grades essays).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_answers')) {
            return;
        }

        if (! Schema::hasColumn('quiz_answers', 'points_awarded')) {
            return;
        }

        // Skip when the driver already has it nullable (fresh installs that ran
        // an amended baseline, or a re-run).
        $column = collect(Schema::getColumns('quiz_answers'))
            ->firstWhere('name', 'points_awarded');

        if ($column === null || ($column['nullable'] ?? false) === true) {
            return;
        }

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->decimal('points_awarded', 8, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quiz_answers') || ! Schema::hasColumn('quiz_answers', 'points_awarded')) {
            return;
        }

        // Anything still pending becomes a zero rather than being lost.
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->decimal('points_awarded', 8, 2)->default(0)->change();
        });
    }
};
