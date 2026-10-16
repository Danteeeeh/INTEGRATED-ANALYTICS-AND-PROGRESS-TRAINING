<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\GradeSubmission;

/**
 * Grade extraction for the instructor "Extract → Preview → Export → Submit"
 * workflow.
 *
 * Rules enforced here:
 *  - only ACTIVE enrollments of the given class are extracted;
 *  - only released + graded LMS items feed a score (via {@see GradeService});
 *  - missing / incomplete grades are flagged, never silently dropped;
 *  - submitting records a snapshot only — it never touches
 *    `enrollments.final_grade` and never writes to AA. AA stays the source of
 *    truth for official final grades.
 *
 * The caller is responsible for authorizing the class against the instructor
 * (see the controller), so this service stays free of request concerns.
 */
class GradeExtractionService
{
    public function __construct(private GradeService $grades) {}

    /**
     * Extract the LMS assessment scores for every active student in the class.
     *
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function extract(ClassModel $class): array
    {
        $class->loadMissing(['course', 'academicPeriod']);

        $enrollments = Enrollment::query()
            ->where('class_id', $class->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->with('student')
            ->orderBy('id')
            ->get();

        $studentIds = $enrollments->pluck('student_id')->unique()->values()->all();
        $summaries = $this->grades->computeClassGradeSummaries($class->id, $studentIds);

        $rows = [];
        $scored = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            $summary = $summaries[$enrollment->student_id] ?? null;

            $isGraded = (bool) ($summary['is_graded'] ?? false);
            $percent = $isGraded ? (float) $summary['percent'] : null;

            if ($percent !== null) {
                $scored[] = $percent;
            }

            $rows[] = [
                'student_id' => $enrollment->student_id,
                'identifier' => $student?->identifier,
                'name' => $student?->name,
                'email' => $student?->email,
                'enrollment_status' => $enrollment->status,
                'graded_items' => (int) ($summary['graded_items'] ?? 0),
                'lms_score' => $percent,
                'letter_grade' => $isGraded ? ($summary['letter_grade'] ?? null) : null,
                'is_graded' => $isGraded,
                'has_missing_grade' => ! $isGraded,
            ];
        }

        $studentCount = count($rows);
        $gradedCount = count($scored);
        $missingCount = $studentCount - $gradedCount;

        $average = $gradedCount > 0
            ? round(array_sum($scored) / $gradedCount, 2)
            : null;

        return [
            'rows' => $rows,
            'summary' => [
                'class_id' => $class->id,
                'class_code' => $class->code,
                'course_code' => $class->course?->code,
                'course_title' => $class->course?->title,
                'period_label' => $class->academicPeriod?->name,
                'student_count' => $studentCount,
                'graded_count' => $gradedCount,
                'missing_count' => $missingCount,
                'average_percent' => $average,
                'is_complete' => $studentCount > 0 && $missingCount === 0,
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * The preview step: exactly the extraction, kept as a named entry point so
     * the intent reads clearly at the call site.
     *
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function preview(ClassModel $class): array
    {
        return $this->extract($class);
    }

    /**
     * Record a submission for the class.
     *
     * Writes ONE row to `grade_submissions` and an audit entry. It does not
     * modify `enrollments`, `grades`, or anything on the AA side.
     */
    public function submit(ClassModel $class, int $userId, ?string $notes = null): GradeSubmission
    {
        $extraction = $this->extract($class);
        $summary = $extraction['summary'];

        $submission = GradeSubmission::create([
            'class_id' => $class->id,
            'instructor_id' => $class->instructor_id,
            'submitted_by' => $userId,
            'status' => GradeSubmission::STATUS_SUBMITTED,
            'period_label' => $summary['period_label'],
            'student_count' => $summary['student_count'],
            'graded_count' => $summary['graded_count'],
            'missing_count' => $summary['missing_count'],
            'average_percent' => $summary['average_percent'],
            'payload' => $extraction,
            'notes' => $notes,
            'submitted_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'grade_submission',
            'resource_type' => GradeSubmission::class,
            'resource_id' => $submission->id,
            'new_values' => [
                'class_id' => $class->id,
                'students' => $summary['student_count'],
                'graded' => $summary['graded_count'],
                'missing' => $summary['missing_count'],
            ],
        ]);

        return $submission;
    }

    /**
     * CSV rows for the preview/export step.
     *
     * The header matches the faculty dashboard's "For AA" export so AA sees one
     * consistent shape, and the final grade is intentionally absent: it is not
     * the LMS's to publish.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function csvRows(ClassModel $class): array
    {
        $extraction = $this->extract($class);

        $rows = [[
            'Student ID',
            'Student Name',
            'Email',
            'Course Code',
            'Course Title',
            'Class Code',
            'Enrollment Status',
            'LMS Score %',
            'Letter Grade',
            'Missing Grade',
        ]];

        foreach ($extraction['rows'] as $row) {
            $rows[] = [
                $row['identifier'] ?? $row['student_id'],
                $row['name'] ?? '',
                $row['email'] ?? '',
                $extraction['summary']['course_code'] ?? '',
                $extraction['summary']['course_title'] ?? '',
                $extraction['summary']['class_code'] ?? '',
                $row['enrollment_status'],
                $row['is_graded'] ? number_format((float) $row['lms_score'], 2) : '',
                $row['letter_grade'] ?? '',
                $row['has_missing_grade'] ? 'Yes' : 'No',
            ];
        }

        return $rows;
    }
}
