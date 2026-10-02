<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\CourseCompletion;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\User;
use Illuminate\Support\Str;

class ReportImportService
{
    public function importStudents(string $absolutePath, ?string $requiredRoleSlug = 'student'): array
    {
        return app(UserService::class)->importFromCsv($absolutePath, $requiredRoleSlug);
    }

    public function importCompletions(string $absolutePath): array
    {
        return $this->runImport($absolutePath, function (array $record) {
            $student = $this->resolveStudent($record);
            $class = $this->resolveClass($record);

            $completion = CourseCompletion::withTrashed()->where([
                'class_id' => $class->id,
                'student_id' => $student->id,
            ])->first() ?? new CourseCompletion(['class_id' => $class->id, 'student_id' => $student->id]);

            $completion->fill([
                'completion_percent' => ($record['completion_percent'] ?? '') !== '' ? (float) $record['completion_percent'] : 100,
                'final_grade' => ($record['final_grade'] ?? '') !== '' ? (float) $record['final_grade'] : null,
                'completed_at' => ($record['completed_at'] ?? '') !== '' ? $this->parseDateTime($record['completed_at']) : now(),
                'requirements_met' => ($record['requirements_met'] ?? '') !== '' ? $this->parseRequirements((string) $record['requirements_met']) : null,
            ]);

            $completion->save();

            return 'created';
        });
    }

    public function importAttendance(string $absolutePath): array
    {
        return $this->runImport($absolutePath, function (array $record) {
            $student = $this->resolveStudent($record);
            $class = $this->resolveClass($record);
            $status = strtolower(trim((string) ($record['status'] ?? 'present')));

            if (! in_array($status, ['present', 'late', 'absent', 'excused'], true)) {
                throw new \InvalidArgumentException("Invalid attendance status '{$status}'.");
            }

            $date = $this->parseDate($record['attendance_date'] ?? date('Y-m-d'));

            AttendanceRecord::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                    'attendance_date' => $date,
                ],
                [
                    'session_title' => ($record['session_title'] ?? '') !== '' ? (string) $record['session_title'] : null,
                    'status' => $status,
                    'joined_at' => ($record['joined_at'] ?? '') !== '' ? $this->parseDateTime($record['joined_at']) : null,
                    'left_at' => ($record['left_at'] ?? '') !== '' ? $this->parseDateTime($record['left_at']) : null,
                    'duration_minutes' => ($record['duration_minutes'] ?? '') !== '' ? (int) $record['duration_minutes'] : null,
                    'notes' => ($record['notes'] ?? '') !== '' ? (string) $record['notes'] : null,
                ]
            );

            return 'created';
        });
    }

    public function importGrades(string $absolutePath, int $gradedBy): array
    {
        return $this->runImport($absolutePath, function (array $record) use ($gradedBy) {
            $student = $this->resolveStudent($record);
            $class = $this->resolveClass($record);
            $item = $this->resolveGradeItem($class, $record);

            $points = ($record['points'] ?? '') !== '' ? (float) $record['points'] : null;
            $maxPoints = $item->max_points ? (float) $item->max_points : 0;
            $scorePercent = ($record['score_percent'] ?? '') !== '' ? (float) $record['score_percent'] : (($points !== null && $maxPoints > 0) ? ($points / $maxPoints) * 100 : null);

            Grade::updateOrCreate(
                [
                    'grade_item_id' => $item->id,
                    'student_id' => $student->id,
                ],
                [
                    'points' => $points,
                    'score_percent' => $scorePercent,
                    'letter_grade' => ($record['letter_grade'] ?? '') !== '' ? strtoupper((string) $record['letter_grade']) : ($scorePercent !== null ? $this->letterGrade($scorePercent) : null),
                    'feedback' => ($record['feedback'] ?? '') !== '' ? (string) $record['feedback'] : null,
                    'graded_by' => $gradedBy,
                    'graded_at' => now(),
                ]
            );

            return 'created';
        });
    }

    protected function runImport(string $absolutePath, callable $rowHandler): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to read the import file.');
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                throw new \RuntimeException('The import file is empty or missing a header row.');
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
                    $result = $rowHandler($record);
                    if ($result === 'updated') {
                        $updated++;
                    } else {
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Row {$rowNumber}: ".$e->getMessage();
                }
            }
        } finally {
            fclose($handle);
        }

        return compact('created', 'updated', 'errors');
    }

    protected function rowIsEmpty(array $row): bool
    {
        return trim(implode('', $row)) === '';
    }

    protected function resolveStudent(array $record): User
    {
        $email = (string) ($record['student_email'] ?? $record['email'] ?? '');
        $identifier = (string) ($record['student_identifier'] ?? $record['identifier'] ?? '');

        $user = $this->findStudent($email, $identifier);

        if (! $user) {
            $value = $email !== '' ? $email : $identifier;
            throw new \InvalidArgumentException("No student found for '{$value}'.");
        }

        return $user;
    }

    protected function resolveClass(array $record): ClassModel
    {
        $code = trim((string) ($record['class_code'] ?? $record['class'] ?? ''));
        if ($code === '') {
            throw new \InvalidArgumentException('class_code is required.');
        }

        $class = ClassModel::where('code', $code)->first();
        if (! $class) {
            throw new \InvalidArgumentException("No class found for code '{$code}'.");
        }

        return $class;
    }

    protected function resolveGradeItem(ClassModel $class, array $record): GradeItem
    {
        $title = trim((string) ($record['item_title'] ?? $record['item'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('item_title is required.');
        }

        $item = GradeItem::where('class_id', $class->id)->where('title', $title)->first();
        if (! $item) {
            throw new \InvalidArgumentException("No grade item named '{$title}' found in class '{$class->code}'.");
        }

        return $item;
    }

    protected function findStudent(string $email, string $identifier): ?User
    {
        if ($email !== '') {
            $user = User::where('email', $email)->first();
            if ($user) {
                return $user;
            }
        }

        if ($identifier !== '') {
            return User::where('identifier', $identifier)->first();
        }

        return null;
    }

    protected function parseDate(string $value): ?string
    {
        return date('Y-m-d', strtotime($value)) ?: null;
    }

    protected function parseDateTime(string $value): ?string
    {
        $ts = strtotime($value);
        return $ts === false ? null : date('Y-m-d H:i:s', $ts);
    }

    protected function parseRequirements(string $value): ?array
    {
        if (Str::lower($value) === 'all') {
            return ['all' => true];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    protected function letterGrade(float $percent): string
    {
        return match (true) {
            $percent >= 90 => 'A',
            $percent >= 80 => 'B',
            $percent >= 70 => 'C',
            $percent >= 60 => 'D',
            default => 'F',
        };
    }
}