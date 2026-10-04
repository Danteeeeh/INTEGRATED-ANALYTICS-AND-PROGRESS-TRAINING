<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuizImportService
{
    public function importQuestionsFromFile(string $filePath, Quiz $quiz, int $userId, ?QuestionBank $questionBank = null): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => $this->importFromCsv($filePath, $quiz, $userId, $questionBank),
            'txt' => $this->importFromPlainText($filePath, $quiz, $userId, $questionBank),
            default => throw new \InvalidArgumentException("Unsupported file format: {$extension}. Supported formats: CSV, TXT"),
        };
    }

    protected function importFromCsv(string $filePath, Quiz $quiz, int $userId, ?QuestionBank $questionBank = null): array
    {
        $created = 0;
        $errors = [];

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to read the CSV file.');
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                throw new \RuntimeException('The CSV file is empty or missing a header row.');
            }

            $header = array_map(fn ($column) => Str::of((string) $column)->trim()->lower()->replace(' ', '_')->toString(), $header);

            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $record = [];
                foreach ($header as $index => $column) {
                    $record[$column] = isset($row[$index]) ? trim((string) $row[$index]) : '';
                }

                try {
                    $this->createQuestionFromRecord($record, $quiz, $userId, $questionBank);
                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }
            }
        } finally {
            fclose($handle);
        }

        return compact('created', 'errors');
    }

    protected function importFromPlainText(string $filePath, Quiz $quiz, int $userId, ?QuestionBank $questionBank = null): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException('Unable to read the text file.');
        }

        $created = 0;
        $errors = [];
        $lines = explode("\n", $content);
        $currentQuestion = null;
        $currentChoices = [];
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);

            if (empty($line)) {
                if ($currentQuestion !== null) {
                    try {
                        $this->createQuestionFromPlainText($currentQuestion, $currentChoices, $quiz, $userId, $questionBank);
                        $created++;
                    } catch (\Throwable $e) {
                        $errors[] = "Line {$lineNumber}: " . $e->getMessage();
                    }
                    $currentQuestion = null;
                    $currentChoices = [];
                }
                continue;
            }

            if (preg_match('/^(\d+)[.)\s]+(.+)$/', $line, $matches)) {
                if ($currentQuestion !== null) {
                    try {
                        $this->createQuestionFromPlainText($currentQuestion, $currentChoices, $quiz, $userId, $questionBank);
                        $created++;
                    } catch (\Throwable $e) {
                        $errors[] = "Line {$lineNumber}: " . $e->getMessage();
                    }
                    $currentChoices = [];
                }
                $currentQuestion = [
                    'question_text' => $matches[2],
                    'question_type' => 'multiple_choice',
                    'default_points' => 1,
                    'difficulty' => 'medium',
                ];
            } elseif (preg_match('/^[a-eA-E][.)\s]+(.+)(\*?)$/', $line, $matches)) {
                $isCorrect = ! empty($matches[2]);
                $currentChoices[] = [
                    'choice_text' => $matches[1],
                    'is_correct' => $isCorrect,
                ];
            } elseif (preg_match('/^Type:\s*(.+)$/i', $line, $matches)) {
                if ($currentQuestion !== null) {
                    $currentQuestion['question_type'] = $this->mapQuestionType($matches[1]);
                }
            } elseif (preg_match('/^Points:\s*(\d+)$/i', $line, $matches)) {
                if ($currentQuestion !== null) {
                    $currentQuestion['default_points'] = (int) $matches[1];
                }
            } elseif (preg_match('/^Answer:\s*(.+)$/i', $line, $matches) && $currentQuestion !== null) {
                $currentQuestion['correct_answer'] = $matches[1];
            }
        }

        if ($currentQuestion !== null) {
            try {
                $this->createQuestionFromPlainText($currentQuestion, $currentChoices, $quiz, $userId, $questionBank);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "End of file: " . $e->getMessage();
            }
        }

        return compact('created', 'errors');
    }

    protected function createQuestionFromRecord(array $record, Quiz $quiz, int $userId, ?QuestionBank $questionBank = null): Question
    {
        $questionType = $this->mapQuestionType($record['question_type'] ?? $record['type'] ?? 'multiple_choice');
        $questionText = $record['question_text'] ?? $record['question'] ?? '';
        if (empty($questionText)) {
            throw new \InvalidArgumentException('Question text is required.');
        }

        return DB::transaction(function () use ($record, $questionType, $questionText, $quiz, $userId, $questionBank) {
            $question = Question::create([
                'question_bank_id' => $questionBank?->id,
                'question_type' => $questionType,
                'question_text' => $questionText,
                'explanation' => $record['explanation'] ?? null,
                'difficulty' => $record['difficulty'] ?? 'medium',
                'default_points' => (float) ($record['points'] ?? $record['default_points'] ?? 1),
                'tags' => ! empty($record['tags']) ? array_map('trim', explode(',', $record['tags'])) : [],
                'created_by' => $userId,
                'status' => 'active',
            ]);

            $position = $quiz->questions()->count() + 1;

            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_id' => $question->id,
                'position' => $position,
                'points' => (float) ($record['points'] ?? $record['default_points'] ?? 1),
                'is_required' => (bool) ($record['is_required'] ?? true),
                'pool_size' => null,
            ]);

            if (in_array($questionType, ['multiple_choice', 'multiple_answer', 'true_false'], true)) {
                $this->createChoicesForQuestion($question, $record);
            }

            return $question;
        });
    }

    protected function createQuestionFromPlainText(array $questionData, array $choices, Quiz $quiz, int $userId, ?QuestionBank $questionBank = null): Question
    {
        return DB::transaction(function () use ($questionData, $choices, $quiz, $userId, $questionBank) {
            $question = Question::create([
                'question_bank_id' => $questionBank?->id,
                'question_type' => $questionData['question_type'],
                'question_text' => $questionData['question_text'],
                'explanation' => $questionData['explanation'] ?? null,
                'difficulty' => $questionData['difficulty'] ?? 'medium',
                'default_points' => (float) ($questionData['default_points'] ?? 1),
                'tags' => [],
                'created_by' => $userId,
                'status' => 'active',
            ]);

            $position = $quiz->questions()->count() + 1;

            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_id' => $question->id,
                'position' => $position,
                'points' => (float) ($questionData['default_points'] ?? 1),
                'is_required' => true,
                'pool_size' => null,
            ]);

            if (! empty($choices) && in_array($questionData['question_type'], ['multiple_choice', 'multiple_answer', 'true_false'], true)) {
                foreach ($choices as $index => $choiceData) {
                    QuestionChoice::create([
                        'question_id' => $question->id,
                        'choice_text' => $choiceData['choice_text'],
                        'is_correct' => $choiceData['is_correct'] ?? false,
                        'position' => $index + 1,
                        'points' => 0,
                        'feedback' => null,
                    ]);
                }
            }

            return $question;
        });
    }

    protected function createChoicesForQuestion(Question $question, array $record): void
    {
        $choiceColumns = [
            'choice_1', 'choice_2', 'choice_3', 'choice_4', 'choice_5',
            'a', 'b', 'c', 'd', 'e',
        ];

        $correctChoice = strtolower($record['correct_answer'] ?? $record['answer'] ?? '');

        foreach ($choiceColumns as $index => $column) {
            if (! isset($record[$column]) || empty($record[$column])) {
                continue;
            }

            $choiceLetter = $index < 5 ? chr(97 + $index) : $column;
            $isCorrect = strtolower($correctChoice) === $choiceLetter;

            QuestionChoice::create([
                'question_id' => $question->id,
                'choice_text' => $record[$column],
                'is_correct' => $isCorrect,
                'position' => $index + 1,
                'points' => 0,
                'feedback' => null,
            ]);
        }
    }

    protected function mapQuestionType(string $type): string
    {
        $type = strtolower(trim($type));

        return match (true) {
            in_array($type, ['mc', 'multiple choice', 'multiple_choice', 'mcq']) => 'multiple_choice',
            in_array($type, ['ma', 'multiple answer', 'multiple_answer', 'checkbox']) => 'multiple_answer',
            in_array($type, ['tf', 'true false', 'true_false', 'true/false']) => 'true_false',
            in_array($type, ['id', 'identification', 'fill']) => 'identification',
            in_array($type, ['sa', 'short answer', 'short_answer']) => 'short_answer',
            in_array($type, ['essay', 'long answer']) => 'essay',
            default => 'multiple_choice',
        };
    }

    protected function rowIsEmpty(array $row): bool
    {
        return trim(implode('', $row)) === '';
    }
}
