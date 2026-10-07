<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs text that was stored after a UTF-8 -> Windows-1252 -> UTF-8 round trip.
 *
 * Symptom: names render as "CS101 ÃƒÂ¢Ãâ€šÂ¬Ãâ‚¬Â Questions" or a category reads
 * "Ã‚Â¢Ã‚Â¬Ã‚Â¬â'Â¬Å'Â¬Ã‚Â¬" instead of "Category 1". The bytes are intact, just
 * re-encoded one level too many, so undoing the round trip restores the
 * original text.
 *
 * Dry-run by default; pass --apply to write.
 */
class RepairMojibake extends Command
{
    protected $signature = 'lms:repair-mojibake
                            {--apply : Actually write the repaired values (default is a dry run)}
                            {--tables= : Comma-separated tables to scan (default: question_banks, question_categories, questions)}';

    protected $description = 'Repair double-encoded (mojibake) text in seeded name/title columns';

    /**
     * How many levels of re-encoding to undo.
     *
     * A single accidental round trip is the common case, but a file that was
     * written twice (for example restored from a mis-encoded backup) needs two.
     */
    private const MAX_PASSES = 3;

    /** Columns that hold human-authored text, per table. */
    private const COLUMNS = [
        'question_banks' => ['title', 'description'],
        'question_categories' => ['name', 'description'],
        'questions' => ['question_text'],
    ];

    public function handle(): int
    {
        $tables = $this->option('tables')
            ? array_map('trim', explode(',', (string) $this->option('tables')))
            : array_keys(self::COLUMNS);

        $apply = (bool) $this->option('apply');
        $totalFixed = 0;
        $skipped = 0;
        $samples = [];

        foreach ($tables as $table) {
            if (! isset(self::COLUMNS[$table])) {
                $this->warn("Skipping unknown table [{$table}].");

                continue;
            }

            if (! Schema::hasTable($table)) {
                $this->warn("Skipping missing table [{$table}].");

                continue;
            }

            foreach (self::COLUMNS[$table] as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $rows = DB::table($table)->select('id', $column)->orderBy('id')->get();
                $fixed = 0;

                foreach ($rows as $row) {
                    $value = (string) $row->{$column};

                    if ($value === '' || ! $this->looksCorrupted($value)) {
                        continue;
                    }

                    $repaired = $this->repair($value);

                    if ($repaired === $value) {
                        $skipped++;

                        continue;
                    }

                    $fixed++;
                    $totalFixed++;

                    if (count($samples) < 10) {
                        $samples[] = sprintf(
                            '%s.%s#%s: %s',
                            $table,
                            $column,
                            $row->id,
                            $repaired
                        );
                    }

                    if ($apply) {
                        DB::table($table)->where('id', $row->id)->update([$column => $repaired]);
                    }
                }

                if ($fixed > 0) {
                    $this->line(sprintf('  %-20s %-16s %d row(s)', $table, $column, $fixed));
                }
            }
        }

        if ($totalFixed === 0) {
            $this->info('Nothing to repair - no double-encoded text found.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Repaired values:');

        foreach ($samples as $sample) {
            $this->line('  '.$sample);
        }

        if ($totalFixed > count($samples)) {
            $this->line(sprintf('  ... and %d more.', $totalFixed - count($samples)));
        }

        $this->newLine();

        if ($skipped > 0) {
            $this->warn("{$skipped} value(s) matched the pattern but could not be safely repaired; left unchanged.");
        }

        if ($apply) {
            $this->info("Fixed {$totalFixed} value(s).");

            return self::SUCCESS;
        }

        $this->warn("Dry run - {$totalFixed} value(s) would change. Re-run with --apply to write them.");

        return self::SUCCESS;
    }

    /**
     * Undo the re-encoding, one level at a time, until the text stops changing.
     */
    private function repair(string $value): string
    {
        for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
            if (! $this->looksCorrupted($value)) {
                break;
            }

            // The corruption re-encoded each original byte as if it were a
            // single Windows-1252 character. Mapping those characters back to
            // their original bytes reverses exactly one round trip.
            $candidate = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

            if ($candidate === $value || ! $this->isSafeRewrite($value, $candidate)) {
                break;
            }

            $value = $candidate;
        }

        return $value;
    }

    /**
     * Refuse rewrites that would damage text rather than restore it.
     *
     * Windows-1252 cannot represent every character. When a string is pushed
     * back through that encoding, characters it has no room for silently
     * become "?" - so a rewrite that introduces question marks where the
     * original had none is data loss, not a repair.
     */
    private function isSafeRewrite(string $original, string $candidate): bool
    {
        // Reject invalid UTF-8 outright.
        if (! preg_match('//u', $candidate)) {
            return false;
        }

        // Reject lossy rewrites (CJK, emoji, and other non-latin text).
        $newMarks = substr_count($candidate, '?') - substr_count($original, '?');

        return $newMarks <= 0;
    }

    /**
     * Recognise the byte signature left behind by the round trip.
     *
     * Two shapes cover the observed corruption:
     *
     *  1. A Latin-1 supplement lead byte followed by a C1 control byte, e.g.
     *     "Ãƒ" or "Ã‚". Genuine UTF-8 never places a C1 control in a
     *     continuation position, so this never matches real text such as
     *     "Biología" (U+00C3 U+00AD).
     *
     *  2. The sequence "â€" (U+00E2 U+20AC) - the UTF-8 form of what a single
     *     round trip turns an em dash, en dash, curly quote or bullet into. It
     *     carries no C1 byte, so it needs its own pattern.
     */
    private function looksCorrupted(string $value): bool
    {
        if (! preg_match('//u', $value)) {
            // Invalid UTF-8 stored as text is itself a corruption worth undoing.
            return true;
        }

        // "â" is U+00E2 (C3 A2) and "€" is U+20AC (E2 82 AC).
        return (bool) preg_match('/[\xC2\xC3][\x80-\x9F]|\xC3\xA2\xE2\x82\xAC/', $value);
    }
}