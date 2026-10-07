<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Section;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Backs the "LMS Subjects" list for every role, the "Check Available Scores"
 * drill-down, and the academic record the admin hands to the registrar.
 *
 * A "subject" here is a class block: one course taught to one section in one
 * academic period. That is the unit the registrar's office actually signs off
 * on, so verification is tracked per class rather than per course.
 */
class SubjectService
{
    public function __construct(private GradeService $grades) {}

    /**
     * Paginated subject list scoped to whatever the viewer is allowed to see.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForUser(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        $this->scopeToUser($query, $user);
        $this->applyFilters($query, $filters);

        $rows = $query->paginate($perPage)->withQueryString();

        // Hydrate the derived columns after pagination so we never run a query
        // per row on the whole table.
        $this->decorate($rows->getCollection());

        return $rows;
    }

    /**
     * The shared query behind the subjects list.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        return ClassModel::query()
            ->with([
                'course',
                'section.program',
                'academicPeriod',
                'instructor:id,first_name,last_name',
                'subjectVerifiedBy:id,first_name,last_name',
            ])
            // dropped students must never inflate a headcount
            ->withCount([
                'enrollments as students_count' => fn ($q) => $q->countable(),
            ]);
    }

    /**
     * Restrict the list to the viewer's own subjects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function scopeToUser($query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isInstructor()) {
            $query->where('classes.instructor_id', $user->id);

            return;
        }

        // Students only see the subjects they are actually enrolled in, and
        // never a dropped one — those are subjects they are not taking.
        $query->whereHas('enrollments', fn ($q) => $q
            ->where('enrollments.student_id', $user->id)
            ->countable());
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('classes.code', 'like', "%{$search}%")
                    ->orWhereHas('course', fn ($cq) => $cq
                        ->where('courses.title', 'like', "%{$search}%")
                        ->orWhere('courses.code', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['subject_status']) && in_array($filters['subject_status'], [
            ClassModel::SUBJECT_DRAFT,
            ClassModel::SUBJECT_VERIFIED,
        ], true)) {
            $query->where('classes.subject_status', $filters['subject_status']);
        }

        if (! empty($filters['enrollment_status'])) {
            $query->where('classes.status', $filters['enrollment_status']);
        }

        if (! empty($filters['section_id'])) {
            $query->whereHas('section', fn ($q) => $q->whereKey($filters['section_id']));
        }

        if (! empty($filters['period_id'])) {
            $query->where('classes.academic_period_id', $filters['period_id']);
        }

        if (! empty($filters['program_id'])) {
            $query->whereHas('section.program', fn ($q) => $q->whereKey($filters['program_id']));
        }
    }

    /**
     * Add the display columns the table needs.
     *
     * @param  Collection<int, ClassModel>  $subjects
     */
    protected function decorate(Collection $subjects): void
    {
        foreach ($subjects as $subject) {
            $subject->setAttribute('subject_shortname', $subject->course?->code);
            $subject->setAttribute('subject_name', $subject->course?->title);
            $subject->setAttribute('section_label', $subject->section?->code);
            $subject->setAttribute('enrolled_count', $subject->students_count ?? 0);
            $subject->setAttribute('seat_limit', $subject->capacity);
        }
    }

    /**
     * Grade completeness for one subject — this is what "Check Available
     * Scores" reports.
     *
     * @return array<string, mixed>
     */
    public function scoreAvailability(ClassModel $class): array
    {
        $items = GradeItem::where('class_id', $class->id)
            ->withCount(['grades as graded_count' => fn ($q) => $q->whereNotNull('points')])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $released = $items->where('is_released', true);

        $enrollments = Enrollment::where('class_id', $class->id)
            ->countable()
            ->get(['id', 'student_id', 'status']);

        $studentIds = $enrollments->pluck('student_id')->all();

        $summaries = $this->grades->computeClassGradeSummaries($class->id, $studentIds);

        $graded = collect($studentIds)
            ->filter(fn ($id) => ($summaries[$id]['is_graded'] ?? false))
            ->values();

        $missing = collect($studentIds)
            ->reject(fn ($id) => ($summaries[$id]['is_graded'] ?? false))
            ->values();

        $unreleased = $items->reject(fn ($item) => $item->is_released);

        $blockers = [];

        if ($enrollments->isEmpty()) {
            $blockers[] = 'No students are enrolled in this subject.';
        }

        if ($items->isEmpty()) {
            $blockers[] = 'No grade items have been set up yet.';
        }

        if ($unreleased->isNotEmpty()) {
            $blockers[] = $unreleased->count().' grade item(s) are not released yet.';
        }

        if ($missing->isNotEmpty()) {
            $blockers[] = $missing->count().' student(s) have no computed grade.';
        }

        return [
            'class' => $class,
            'items' => $items,
            'item_count' => $items->count(),
            'released_count' => $released->count(),
            'unreleased_count' => $unreleased->count(),
            'enrollment_count' => $enrollments->count(),
            'graded_count' => $graded->count(),
            'missing_count' => $missing->count(),
            'summaries' => $summaries,
            'missing_student_ids' => $missing->all(),
            'blockers' => $blockers,
            'is_complete' => $blockers === [],
            'class_average' => $this->averageOf($summaries),
        ];
    }

    /**
     * One student's own view of a subject's grades.
     *
     * Deliberately does not reuse {@see scoreAvailability()}: that report is
     * cohort-wide by design (enrollment counts, class average, who is still
     * pending) and is instructor/registrar information. Passing it to a student
     * would expose every classmate's score, so the student's payload is built
     * from scratch and only ever carries that one student's rows.
     *
     * Only released items are described, matching the gradebook: an item that
     * has not been published must not reveal either its existence or a score.
     *
     * @return array<string, mixed>
     */
    public function studentScoreSheet(ClassModel $class, int $studentId): array
    {
        $items = GradeItem::where('class_id', $class->id)
            ->where('is_released', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $grades = Grade::where('student_id', $studentId)
            ->whereIn('grade_item_id', $items->pluck('id'))
            ->get()
            ->keyBy('grade_item_id');

        $summary = $this->grades->computeStudentClassGrade($studentId, $class->id);

        // Decorate each released item with this student's own points, so the
        // view never has to reach for another student's row.
        $rows = $items->map(fn (GradeItem $item) => [
            'item' => $item,
            'points' => $grades[$item->id]->points ?? null,
            'percent' => $grades[$item->id] !== null && $item->max_points > 0
                ? round(((float) $grades[$item->id]->points / (float) $item->max_points) * 100, 2)
                : null,
        ]);

        return [
            'class' => $class,
            'rows' => $rows,
            'item_count' => $items->count(),
            'graded_count' => $rows->filter(fn ($row) => $row['points'] !== null)->count(),
            'summary' => $summary,
        ];
    }

    /**
     * Mean of every graded student's class grade.
     *
     * @param  array<int, array<string, mixed>>  $summaries
     */
    protected function averageOf(array $summaries): float
    {
        $percents = collect($summaries)
            ->pluck('percent')
            ->filter(fn ($percent) => $percent !== null)
            ->map(fn ($percent) => (float) $percent)
            ->values();

        return $percents->isEmpty() ? 0.0 : round((float) $percents->avg(), 2);
    }

    /**
     * Build a full academic record (transcript) for one student.
     *
     * Every line is the canonical GradeService computation, so the record the
     * registrar receives matches the gradebook exactly.
     *
     * @return array<string, mixed>
     */
    public function academicRecord(User $student, ?int $periodId = null): array
    {
        $enrollments = Enrollment::with([
            'class.course',
            'class.section.program.department',
            'class.academicPeriod',
            'class.instructor:id,first_name,last_name',
        ])
            ->where('student_id', $student->id)
            ->countable()
            ->when($periodId, fn ($q) => $q->whereHas(
                'class',
                fn ($cq) => $cq->where('academic_period_id', $periodId)
            ))
            ->orderBy('enrollments.enrolled_at')
            ->get();

        // Per class, not merged: a transcript needs one distinct line per subject.
        $summariesByClass = $this->grades->computeSummariesByClass($enrollments);

        $lines = [];

        foreach ($enrollments as $enrollment) {
            $class = $enrollment->class;
            $summary = $summariesByClass[$enrollment->class_id][$enrollment->student_id] ?? null;

            $lines[] = [
                'enrollment_id' => $enrollment->id,
                'class_id' => $class?->id,
                'code' => $class?->code,
                'shortname' => $class?->course?->code,
                'name' => $class?->course?->title,
                'section' => $class?->section?->code,
                'program' => $class?->section?->program?->name,
                'period' => $class?->academicPeriod?->name,
                'instructor' => $class?->instructor?->name,
                'units' => (float) ($class?->course?->credits ?? 0),
                'subject_status' => $class?->subject_status ?? ClassModel::SUBJECT_DRAFT,
                'is_verified' => $class?->isSubjectVerified() ?? false,
                'final_grade' => $summary['percent'] ?? null,
                'letter_grade' => $summary['letter_grade'] ?? null,
                'is_graded' => $summary['is_graded'] ?? false,
                'remark' => $this->remarkFor($class?->subject_status, $summary['is_graded'] ?? false),
            ];
        }

        $graded = collect($lines)->filter(fn ($line) => $line['is_graded']);

        return [
            'student' => $student,
            'program' => $student->program?->name,
            'department' => $student->department?->name ?? $student->program?->department?->name,
            'section' => $student->section?->code,
            'identifier' => $student->identifier,
            'lines' => $lines,
            'graded_count' => $graded->count(),
            'total_count' => count($lines),
            'unverified_count' => collect($lines)->reject(fn ($line) => $line['is_verified'])->count(),
            'gwa' => $this->weightedGwa($graded),
            'units_earned' => round((float) $graded->sum('units'), 2),
            'overall_remark' => $this->overallRemark($this->weightedGwa($graded)),
        ];
    }

    /**
     * General weighted average, weighted by course credits.
     *
     * A plain average of subject percentages would treat a 3-unit and a 5-unit
     * subject as equal, which is not how a transcript reads. Courses with no
     * credits set still count, using a weight of 1, so a record is never blank
     * just because credits were never filled in.
     *
     * @param  Collection<int, array<string, mixed>>  $graded
     */
    protected function weightedGwa(Collection $graded): ?float
    {
        if ($graded->isEmpty()) {
            return null;
        }

        $weighted = 0.0;
        $totalUnits = 0.0;

        foreach ($graded as $line) {
            $weight = (float) ($line['units'] ?? 0) ?: 1.0;

            $weighted += (float) $line['final_grade'] * $weight;
            $totalUnits += $weight;
        }

        return $totalUnits > 0 ? round($weighted / $totalUnits, 2) : null;
    }

    /**
     * @param  array<string, mixed>|null  $summary
     */
    protected function remarkFor(?string $subjectStatus, bool $isGraded): string
    {
        if (! $isGraded) {
            return 'Pending';
        }

        if ($subjectStatus === ClassModel::SUBJECT_VERIFIED) {
            return 'Verified';
        }

        return 'Unverified';
    }

    protected function overallRemark(?float $gwa): string
    {
        if ($gwa === null) {
            return 'No grades on record';
        }

        $passing = (float) config('lms.passing_grade', 60);

        return $gwa >= $passing ? 'Passed' : 'Below passing grade';
    }

    /**
     * Sections and periods for the filter dropdowns.
     *
     * @return array{sections: Collection<int, Section>, periods: Collection<int, mixed>}
     */
    public function filterOptions(): array
    {
        return [
            'sections' => Section::with('program')->orderBy('code')->get(),
            'periods' => \App\Models\AcademicPeriod::orderByDesc('start_date')->get(),
        ];
    }
}