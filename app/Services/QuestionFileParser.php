<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Reads a question file into normalised records.
 *
 * Quizzes and exams both import from the same CSV and plain-text formats, so the
 * parsing lives here once. Every record comes back in one shape, which is what
 * lets a single import routine attach them to either kind of assessment:
 *
 *   question_text  string
 *   question_type  string   one of the Question::TYPE_* values
 *   explanation    ?string
 *   difficulty     ?string
 *   points         float
 *   tags           string[]
 *   choices        array<int, array{choice_text: string, is_correct: bool}>
 *
 * Parsing is deliberately separate from writing: the caller decides which
 * pivot table the questions end up in, and how many points they carry.
 */
class QuestionFileParser
{
    /** Formats this parser can read. */
    public const SUPPORTED = ['csv', 'txt'];

    private const CHOICE_COLUMNS = ['choice_1', 'choice_2', 'choice_3', 'choice_4', 'choice_5'];

    /**
     * @return array{records: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function parse(string $filePath, ?string $extension = null): array
    {
        $extension = strtolower($extension ?: pathinfo($filePath, PATHINFO_EXTENSION));

        if (! in_array($extension, self::SUPPORTED, true)) {
            throw new \InvalidArgumentException(
                "Unsupported file format: {$extension}. Supported formats: CSV, TXT"
            );
        }

        return $extension === 'csv'
            ? $this->parseCsv($filePath)
            : $this->parsePlainText($filePath);
    }

    /**
     * @return array{records: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    private function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to read the CSV file.');
        }

        $records = [];
        $errors = [];

        try {
            $header = fgetcsv($handle);

            if (! is_array($header)) {
                throw new \RuntimeException('The CSV file is empty or missing a header row.');
            }

            $header = array_map(
                fn ($column) => Str::of((string) $column)->trim()->lower()->replace(' ', '_')->toString(),
                $header
            );

            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $raw = [];

                foreach ($header as $index => $column) {
                    $raw[$column] = isset($row[$index]) ? trim((string) $row[$index]) : '';
                }

                $text = $raw['question_text'] ?? $raw['question'] ?? '';

                if ($text === '') {
                    $errors[] = "Row {$rowNumber}: question text is missing.";

                    continue;
                }

                $records[] = $this->normalise([
                    'question_text' => $text,
                    'question_type' => $raw['question_type'] ?? $raw['type'] ?? 'multiple_choice',
                    'explanation' => ($raw['explanation'] ?? '') ?: null,
                    'difficulty' => ($raw['difficulty'] ?? '') ?: 'medium',
                    'points' => $raw['points'] ?? $raw['default_points'] ?? 1,
                    'tags' => $raw['tags'] ?? '',
                    'choices' => $this->choicesFromColumns($raw),
                    'correct_answer' => $raw['correct_answer'] ?? $raw['answer'] ?? '',
                ]);
            }
        } finally {
            fclose($handle);
        }

        return compact('records', 'errors');
    }

    /**
     * @return array{records: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    private function parsePlainText(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException('Unable to read the text file.');
        }

        $records = [];
        $errors = [];

        $current = null;
        $choices = [];
        $lineNumber = 0;

        $flush = function () use (&$current, &$choices, &$records, &$errors) {
            if ($current === null) {
                return;
            }

            if (trim((string) ($current['question_text'] ?? '')) === '') {
                $errors[] = 'End of file: question text is missing.';

                $current = null;
                $choices = [];

                return;
            }

            $current['choices'] = $choices;
            $records[] = $this->normalise($current);

            $current = null;
            $choices = [];
        };

        foreach (explode("\n", $content) as $line) {
            $lineNumber++;
            $line = trim($line);

            if ($line === '') {
                $flush();

                continue;
            }

            // "1. Question text" starts a new question.
            if (preg_match('/^(\d+)[.)\s]+(.+)$/', $line, $m)) {
                $flush();

                $current = [
                    'question_text' => $m[2],
                    'question_type' => 'multiple_choice',
                    'difficulty' => 'medium',
                    'points' => 1,
                    'explanation' => null,
                ];

                continue;
            }

            // "a. Choice text *" — the trailing asterisk marks the answer.
            if (preg_match('/^([a-eA-E])[.)\s]+(.+?)(\*?)$/', $line, $m)) {
                $choices[] = [
                    'choice_text' => $m[2],
                    'is_correct' => $m[3] === '*',
                ];

                continue;
            }

            if (preg_match('/^Type:\s*(.+)$/i', $line, $m)) {
                if ($current !== null) {
                    $current['question_type'] = $m[1];
                }

                continue;
            }

            if (preg_match('/^Points:\s*(\d+)$/i', $line, $m)) {
                if ($current !== null) {
                    $current['points'] = (int) $m[1];
                }

                continue;
            }

            if (preg_match('/^Explanation:\s*(.+)$/i', $line, $m)) {
                if ($current !== null) {
                    $current['explanation'] = $m[1];
                }

            }
        }

        $flush();

        return compact('records', 'errors');
    }

    /**
     * Turn the choice columns of a CSV row into a normalised list.
     *
     * Answers may be marked either by naming the letter ("b") or by an
     * "is_correct_*" column, so both are honoured.
     *
     * @param  array<string, string>  $raw
     * @return array<int, array{choice_text: string, is_correct: bool}>
     */
    private function choicesFromColumns(array $raw): array
    {
        $correct = strtolower(trim((string) ($raw['correct_answer'] ?? $raw['answer'] ?? '')));
        $choices = [];

        foreach (self::CHOICE_COLUMNS as $index => $column) {
            $text = trim((string) ($raw[$column] ?? ''));

            if ($text === '') {
                continue;
            }

            $letter = chr(97 + $index);

            // An explicit flag column wins over a letter answer.
            $flag = $raw['is_correct_'.($index + 1)] ?? null;

            $choices[] = [
                'choice_text' => $text,
                'is_correct' => $flag !== null
                    ? in_array(strtolower($flag), ['1', 'true', 'yes'], true)
                    : $correct === $letter,
            ];
        }

        return $choices;
    }

    /**
     * Coerce one parsed question into the agreed record shape.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalise(array $data): array
    {
        $type = $this->mapQuestionType((string) ($data['question_type'] ?? 'multiple_choice'));

        // Types that are answered with free text carry no choices.
        $keepsChoices = in_array($type, [
            \App\Models\Question::TYPE_MULTIPLE_CHOICE,
            \App\Models\Question::TYPE_MULTIPLE_ANSWER,
            \App\Models\Question::TYPE_TRUE_FALSE,
        ], true);

        $choices = [];

        foreach (($data['choices'] ?? []) as $choice) {
            $text = trim((string) (is_array($choice) ? ($choice['choice_text'] ?? '') : $choice));

            if ($text === '') {
                continue;
            }

            $choices[] = [
                'choice_text' => $text,
                'is_correct' => (bool) (is_array($choice) ? ($choice['is_correct'] ?? false) : false),
            ];
        }

        $difficulty = strtolower(trim((string) ($data['difficulty'] ?? 'medium')));

        if (! in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'medium';
        }

        return [
            'question_text' => (string) $data['question_text'],
            'question_type' => $type,
            'explanation' => $data['explanation'] ?? null,
            'difficulty' => $difficulty,
            'points' => (float) ($data['points'] ?? 1),
            'tags' => $this->parseTags($data['tags'] ?? ''),
            'choices' => $keepsChoices ? $choices : [],
        ];
    }

    /** @return string[] */
    private function parseTags($tags): array
    {
        if (is_array($tags)) {
            return array_values(array_filter(array_map('trim', $tags)));
        }

        $tags = trim((string) $tags);

        return $tags === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $tags))));
    }

    private function mapQuestionType(string $type): string
    {
        $type = strtolower(trim($type));

        return match (true) {
            in_array($type, ['mc', 'multiple choice', 'multiple_choice', 'mcq'], true) => 'multiple_choice',
            in_array($type, ['ma', 'multiple answer', 'multiple_answer', 'checkbox'], true) => 'multiple_answer',
            in_array($type, ['tf', 'true false', 'true_false', 'true/false'], true) => 'true_false',
            in_array($type, ['id', 'identification', 'fill'], true) => 'identification',
            in_array($type, ['sa', 'short answer', 'short_answer'], true) => 'short_answer',
            in_array($type, ['essay', 'long answer'], true) => 'essay',
            default => 'multiple_choice',
        };
    }

    /** @param  array<int, mixed>  $row */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}