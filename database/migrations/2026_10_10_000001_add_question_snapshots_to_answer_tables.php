<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freezes each question as the student saw it (§14 of the Test Bank spec).
 *
 * quiz_answers.question_id and exam_answers.question_id are both
 * onDelete('cascade'), and the attempt views read $answer->question->question_text
 * live. So editing a Test Bank question rewrote every past attempt, and deleting
 * one destroyed the student's answer rows outright.
 *
 * The snapshot holds the question text, type, choices, correct answer and points
 * as they were at submission time. Attempt views read the snapshot when present,
 * so an instructor may edit or retire the original without touching history.
 */
return new class extends Migration
{
    private const TABLES = ['quiz_answers', 'exam_answers'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'question_snapshot')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->json('question_snapshot')->nullable()->after('question_id');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'question_snapshot')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('question_snapshot');
            });
        }
    }
};
