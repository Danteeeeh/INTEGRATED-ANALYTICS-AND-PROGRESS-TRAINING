<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Exam types become the three semestral assessments: Prelim, Midterm, Finals.
 *
 * "module" and "other" stay allowed on purpose. They were never chosen by an
 * instructor: the create form had no Type field, so every exam saved before
 * that was fixed fell back to the column default of "module". Dropping the
 * value would destroy those exams rather than fix them, and quietly relabelling
 * them as Prelim would be a guess. They are relabelled deliberately instead,
 * with lms:relabel-exams.
 */
return new class extends Migration
{
    /** Values offered to an instructor, in semester order. */
    private const SEMESTRAL = ['prelim', 'midterm', 'finals'];

    /** Legacy values still accepted so old exams remain editable. */
    private const LEGACY = ['module', 'other'];

    public function up(): void
    {
        // Three steps, and the order matters.
        //
        // The target values must exist in the column BEFORE the rows are moved:
        // MySQL coerces an unknown value into '' and SQLite rejects it against
        // its CHECK constraint. So the column is widened to accept both the old
        // and new names first, the rows are remapped, and only then is the
        // column narrowed to the final list.
        $widened = array_merge(self::SEMESTRAL, self::LEGACY, ['final', 'comprehensive']);
        $finalSet = array_merge(self::SEMESTRAL, self::LEGACY);

        $this->setEnum($widened, 'module');

        DB::table('exams')->where('exam_type', 'final')->update(['exam_type' => 'finals']);
        DB::table('exams')->where('exam_type', 'comprehensive')->update(['exam_type' => 'finals']);

        $this->setEnum($finalSet, 'prelim');
    }

    public function down(): void
    {
        $widened = array_merge(self::SEMESTRAL, self::LEGACY, ['final', 'comprehensive']);

        // Same constraint: 'final' has to be allowed before rows can move back.
        $this->setEnum($widened, 'module');

        DB::table('exams')->where('exam_type', 'prelim')->update(['exam_type' => 'midterm']);
        DB::table('exams')->where('exam_type', 'finals')->update(['exam_type' => 'final']);

        $this->setEnum(['midterm', 'final', 'comprehensive', 'module', 'other'], 'module');
    }

    /**
     * Rewrite the exam_type enum, driver by driver.
     *
     * @param  array<int, string>  $values
     */
    private function setEnum(array $values, string $default): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(sprintf(
                'ALTER TABLE `exams` MODIFY `exam_type` ENUM(%s) NOT NULL DEFAULT ?',
                implode(',', array_map(fn ($v) => "'{$v}'", $values))
            ), [$default]);

            return;
        }

        // SQLite enforces enum() through a CHECK constraint and PostgreSQL
        // through its own enum type, so both rebuild the column.
        Schema::table('exams', function (Blueprint $table) use ($values, $default) {
            $table->enum('exam_type', $values)->default($default)->change();
        });
    }
};