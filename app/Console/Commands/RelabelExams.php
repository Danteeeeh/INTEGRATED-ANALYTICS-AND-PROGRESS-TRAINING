<?php

namespace App\Console\Commands;

use App\Models\Exam;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Relabels the exams left on a legacy Type.
 *
 * The instructor create form had no Type field, so every exam saved before that
 * was fixed fell back to the column default "module" — a value no instructor
 * ever chose. Picking the right one per exam is a judgement call, so this
 * lists them and can apply a mapping in bulk rather than guessing.
 *
 * Dry-run by default; pass --apply to write.
 */
class RelabelExams extends Command
{
    protected $signature = 'lms:relabel-exams
                            {--apply : Actually write the new types (default is a dry run)}
                            {--from= : Only relabel this legacy type (default: every legacy type)}
                            {--map= : Bulk mapping, e.g. "Prelim Exam:prelim,Finals:finals"}
                            {--exam= : Relabel a single exam by id, as --exam=12 --map=Prelim Exam:prelim}';

    protected $description = 'Relabel exams still on a legacy Type (module/other) onto Prelim, Midterm or Finals';

    public function handle(): int
    {
        $from = $this->option('from');

        $query = Exam::query()->whereIn('exam_type', $from ? [$from] : [Exam::TYPE_MODULE, Exam::TYPE_OTHER]);

        if ($this->option('exam')) {
            $query->whereKey((int) $this->option('exam'));
        }

        $exams = $query->orderBy('id')->get();

        if ($exams->isEmpty()) {
            $this->info('No legacy-typed exams found — nothing to relabel.');

            return self::SUCCESS;
        }

        $this->line(sprintf('%d exam(s) on a legacy type:', $exams->count()));
        $this->newLine();

        foreach ($exams as $exam) {
            $this->line(sprintf(
                '  #%-5s %-45s class %-10s %s',
                $exam->id,
                \Illuminate\Support\Str::limit((string) $exam->title, 45),
                $exam->class?->code ?? '-',
                $exam->exam_type
            ));
        }

        $this->newLine();
        $this->line('Options accepted: '.implode(', ', array_keys(Exam::typeOptions())));
        $this->warn('Choose the correct type for each. Nothing has been changed yet.');

        $map = $this->parseMap();

        if ($map === []) {
            $this->newLine();
            $this->warn('Dry run - nothing has been changed.');
            $this->line('Relabel one exam at a time with:');
            $this->line('  php artisan lms:relabel-exams --exam=12 --map="Prelim Exam:prelim" --apply');
            $this->line('Or many at once with:');
            $this->line('  php artisan lms:relabel-exams --map="Prelim Exam:prelim,Finals:finals" --apply');

            return self::SUCCESS;
        }

        // Title prefix -> new type. Titles are matched case-insensitively on a
        // whole-word basis so "Midterm" does not match "Midterms Review".
        $changed = 0;
        $unmatched = [];

        foreach ($exams as $exam) {
            $newType = $this->matchTitle((string) $exam->title, $map);

            if ($newType === null) {
                $unmatched[] = $exam;

                continue;
            }

            $changed++;

            $this->line(sprintf('  #%s %s -> %s', $exam->id, \Illuminate\Support\Str::limit($exam->title, 40), $newType));

            if ($this->option('apply')) {
                Exam::withoutEvents(fn () => $exam->forceFill(['exam_type' => $newType])->save());
            }
        }

        $this->newLine();

        if ($changed === 0) {
            $this->warn('No exam title matched anything in --map, so nothing changed.');

            return self::SUCCESS;
        }

        if ($unmatched !== []) {
            $this->warn(sprintf(
                '%d exam(s) matched nothing and keep their current type:',
                count($unmatched)
            ));

            foreach ($unmatched as $exam) {
                $this->warn('  #'.$exam->id.' '.\Illuminate\Support\Str::limit((string) $exam->title, 50));
            }
        }

        if ($this->option('apply')) {
            $this->info("Relabelled {$changed} exam(s).");

            return self::SUCCESS;
        }

        $this->warn("Dry run — {$changed} exam(s) would change. Re-run with --apply to write.");

        return self::SUCCESS;
    }

    /**
     * Parse "Prelim Exam:prelim,Finals:finals" into a lookup of the
     * recognised new types.
     *
     * @return array<string, string>
     */
    private function parseMap(): array
    {
        $raw = (string) $this->option('map');

        if (trim($raw) === '') {
            return [];
        }

        $allowed = array_keys(Exam::typeOptions());
        $map = [];

        foreach (explode(',', $raw) as $pair) {
            if (! str_contains($pair, ':')) {
                continue;
            }

            [$needle, $type] = explode(':', $pair, 2);
            $needle = trim($needle);
            $type = strtolower(trim($type));

            if ($needle === '' || ! in_array($type, $allowed, true)) {
                $this->warn("Ignoring \"{$pair}\": unknown type. Use one of ".implode(', ', $allowed));

                continue;
            }

            $map[$needle] = $type;
        }

        return $map;
    }

    /**
     * Find the new type for a title, longest needle first so a specific label
     * such as "Final Exam" is not swallowed by a shorter "Exam" rule.
     *
     * @param  array<string, string>  $map
     */
    private function matchTitle(string $title, array $map): ?string
    {
        $needles = $map;

        // Longest needle first: "Final Exam" must beat "Exam".
        uksort($needles, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($needles as $needle => $type) {
            if (preg_match('/\b'.preg_quote($needle, '/').'\b/i', $title)) {
                return $type;
            }
        }

        return null;
    }
}