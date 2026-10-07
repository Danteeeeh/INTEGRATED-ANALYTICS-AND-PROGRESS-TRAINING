<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\AssignmentSubmission;
use App\Models\ExamAttempt;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\FeedbackSuggestionService;
use App\Services\GradeBreakdownService;
use App\Services\GradeService;
use App\Services\StudentRiskService;
use App\Models\GradeConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradebookController extends Controller
{
    public function __construct(
        private GradeService $grades,
        private GradeBreakdownService $breakdown,
        private StudentRiskService $risk,
    ) {
    }

    public function index(ClassModel $class): View
    {
        $this->authorize('view', Grade::class, ['class' => $class]);

        $class->load(['gradeItems', 'enrollments.student', 'gradeConfiguration']);

        $students = $class->enrollments()
            ->where('status', 'active')
            ->with(['student', 'student.submissions' => fn ($q) => $q->latest('submitted_at')])
            ->paginate(20);

        $gradeItems = GradeItem::where('class_id', $class->id)
            ->with('grades')
            ->orderBy('position', 'asc')
            ->get();

        // Single source of truth: points-weighted over released + graded items.
        $summaries = $this->grades->computeClassGradeSummaries($class->id);

        $classAverage = collect($summaries)->where('is_graded', true)->avg('percent') ?? 0;

        // Component columns + On Track / At Risk, from the instructor's grading
        // configuration when there is one.
        $configuration = $class->gradeConfiguration;
        $breakdowns = $this->breakdown->forClass($class);
        $risk = $this->risk->evaluateAll($class, $breakdowns);

        $componentColumns = $this->componentColumns($configuration, $breakdowns);
        $riskTally = $this->risk->tally($risk);

        return view('instructor.gradebook.index', compact(
            'class',
            'students',
            'gradeItems',
            'summaries',
            'classAverage',
            'configuration',
            'breakdowns',
            'risk',
            'componentColumns',
            'riskTally',
        ));
    }

    /**
     * Which components get their own gradebook column.
     *
     * Only the enabled ones when a configuration exists; otherwise everything
     * the class actually has grade items for.
     *
     * @param  array<int, array<string, mixed>>  $breakdowns
     * @return array<int, array{type: string, label: string, weight: float}>
     */
    protected function componentColumns(?GradeConfiguration $configuration, array $breakdowns): array
    {
        $columns = [];

        foreach (GradeConfiguration::COMPONENTS as $type => $meta) {
            $weight = $configuration?->weights()[$type] ?? 0;

            if ($configuration) {
                // A configured component earns a column even before anyone has a
                // grade in it, so the instructor can see it is still empty.
                if ($weight <= 0) {
                    continue;
                }
            } else {
                $hasItems = collect($breakdowns)
                    ->contains(fn ($row) => isset($row['components'][$type]));

                if (! $hasItems) {
                    continue;
                }
            }

            $columns[] = [
                'type' => $type,
                'label' => $meta['label'],
                'weight' => round($weight, 2),
            ];
        }

        return $columns;
    }

    public function suggestFeedback(AssignmentSubmission $submission, FeedbackSuggestionService $feedback): JsonResponse
    {
        $this->authorize('view', Grade::class, ['class' => $submission->assignment?->class]);

        return response()->json([
            'draft' => $feedback->suggestForSubmission($submission),
        ]);
    }

    public function storeGrade(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('create', Grade::class, ['class' => $class]);

        $validated = $request->validate([
            'grade_item_id' => 'required|exists:grade_items,id',
            'student_id' => 'required|exists:users,id',
            'points' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $gradeItem = GradeItem::findOrFail($validated['grade_item_id']);
        abort_if($gradeItem->class_id !== $class->id, 403);

        $enrollment = Enrollment::where('class_id', $class->id)
            ->where('student_id', $validated['student_id'])
            ->firstOrFail();

        $scorePercent = $gradeItem->max_points > 0 ? ($validated['points'] / $gradeItem->max_points) * 100 : 0;

        Grade::create([
            'grade_item_id' => $gradeItem->id,
            'student_id' => $validated['student_id'],
            'points' => $validated['points'],
            'score_percent' => $scorePercent,
            'letter_grade' => $this->grades->percentageToLetter((float) $scorePercent),
            'feedback' => $validated['feedback'] ?? null,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        $this->grades->invalidateInstructorDashboardCache($class->id);

        return redirect()->route('instructor.classes.gradebook.index', $class)
            ->with('success', 'Grade saved successfully.');
    }

    public function updateGrade(Request $request, ClassModel $class, Grade $grade): RedirectResponse
    {
        $this->authorize('update', $grade, ['class' => $class]);

        $validated = $request->validate([
            'points' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
            'override_note' => 'nullable|string',
            'is_override' => 'boolean',
        ]);

        $gradeItem = GradeItem::findOrFail($grade->grade_item_id);
        $scorePercent = $gradeItem->max_points > 0 ? ($validated['points'] / $gradeItem->max_points) * 100 : 0;

        $grade->update([
            'points' => $validated['points'],
            'score_percent' => $scorePercent,
            'letter_grade' => $this->grades->percentageToLetter((float) $scorePercent),
            'feedback' => $validated['feedback'] ?? null,
            'override_note' => $validated['override_note'] ?? null,
            'is_override' => $validated['is_override'] ?? false,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        $this->grades->invalidateInstructorDashboardCache($class->id);

        return redirect()->route('instructor.classes.gradebook.index', $class)
            ->with('success', 'Grade updated successfully.');
    }

    public function releaseGrades(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('update', Grade::class, ['class' => $class]);

        $validated = $request->validate([
            'grade_item_ids' => 'nullable|array',
            'grade_item_ids.*' => 'exists:grade_items,id',
        ]);

        $query = GradeItem::where('class_id', $class->id);

        if (isset($validated['grade_item_ids']) && count($validated['grade_item_ids']) > 0) {
            $query->whereIn('id', $validated['grade_item_ids']);
        }

        $query->update([
            'is_released' => true,
            'released_at' => now(),
        ]);

        // Releasing changes what counts towards a class grade.
        $this->grades->invalidateInstructorDashboardCache($class->id);

        return redirect()->route('instructor.classes.gradebook.index', $class)
            ->with('success', 'Grades released successfully.');
    }

    public function storeBulkGrades(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('create', Grade::class, ['class' => $class]);

        $validated = $request->validate([
            'grade_item_id' => 'required|exists:grade_items,id',
            'grades' => 'required|array',
            'grades.*' => 'nullable|numeric|min:0',
            'feedback' => 'nullable|array',
            'feedback.*' => 'nullable|string',
        ]);

        $gradeItem = GradeItem::findOrFail($validated['grade_item_id']);
        abort_if($gradeItem->class_id !== $class->id, 403);

        $saved = 0;

        foreach ($validated['grades'] as $studentId => $points) {
            if ($points === null || $points === '') {
                continue;
            }

            // Only grade students actually enrolled in this class
            $enrolled = Enrollment::where('class_id', $class->id)
                ->where('student_id', $studentId)
                ->exists();

            if (! $enrolled) {
                continue;
            }

            $points = min((float) $points, (float) $gradeItem->max_points);
            $scorePercent = $gradeItem->max_points > 0 ? ($points / $gradeItem->max_points) * 100 : 0;

            Grade::updateOrCreate(
                [
                    'grade_item_id' => $gradeItem->id,
                    'student_id' => $studentId,
                ],
                [
                    'points' => $points,
                    'score_percent' => $scorePercent,
                    'letter_grade' => $this->grades->percentageToLetter((float) $scorePercent),
                    'feedback' => $validated['feedback'][$studentId] ?? null,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                ]
            );

            $saved++;
        }

        if ($saved > 0) {
            $this->grades->invalidateInstructorDashboardCache($class->id);
        }

        return redirect()->route('instructor.classes.gradebook.index', $class)
            ->with('success', "Bulk grades saved for {$saved} student(s).");
    }

    public function export(Request $request, ClassModel $class): StreamedResponse
    {
        $this->authorize('view', Grade::class, ['class' => $class]);

        $gradeItems = GradeItem::where('class_id', $class->id)
            ->with('grades')
            ->orderBy('position', 'asc')
            ->get();

        $enrollments = $class->enrollments()
            ->where('status', 'active')
            ->with('student')
            ->get();

        $summaries = $this->grades->computeClassGradeSummaries(
            $class->id,
            $enrollments->pluck('student_id')->all()
        );

        $filename = 'gradebook-'.Str::slug($class->code ?? 'class-'.$class->id).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($gradeItems, $enrollments, $summaries) {
            $out = fopen('php://output', 'w');

            // Header row: student info + one column per grade item
            $header = ['Student Name', 'Email'];
            foreach ($gradeItems as $item) {
                $header[] = $item->title.' (/'.$item->max_points.')'.($item->is_released ? '' : ' [hidden]');
            }
            $header[] = 'Total Earned';
            $header[] = 'Total Possible';
            $header[] = 'Percent';
            $header[] = 'Letter';
            fputcsv($out, $header);

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                $row = [$student?->name ?? 'Unknown', $student?->email ?? ''];

                foreach ($gradeItems as $item) {
                    // Never leak unreleased scores to the CSV.
                    if (! $item->is_released) {
                        $row[] = '';
                        continue;
                    }
                    $grade = $item->grades->firstWhere('student_id', $enrollment->student_id);
                    $row[] = $grade ? $grade->points : '';
                }

                $summary = $summaries[$enrollment->student_id] ?? null;

                $row[] = $summary['earned_points'] ?? 0;
                $row[] = $summary['max_points'] ?? 0;
                $row[] = ($summary['is_graded'] ?? false)
                    ? number_format($summary['percent'], 1).'%'
                    : '—';
                $row[] = ($summary['is_graded'] ?? false) ? $summary['letter_grade'] : '—';

                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function studentGrades(ClassModel $class, User $student): View
    {
        $this->authorize('view', Grade::class, ['class' => $class]);

        $enrollment = Enrollment::where('class_id', $class->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $gradeItems = GradeItem::where('class_id', $class->id)
            ->with(['grades' => fn ($q) => $q->where('student_id', $student->id)])
            ->orderBy('position', 'asc')
            ->get();

        // Quiz attempts for this student in this class
        $quizAttempts = QuizAttempt::where('student_id', $student->id)
            ->whereHas('quiz', function ($q) use ($class) {
                $q->where('class_id', $class->id);
            })
            ->with(['quiz', 'answers.question'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Exam attempts for this student in this class
        $examAttempts = ExamAttempt::where('student_id', $student->id)
            ->whereHas('exam', function ($q) use ($class) {
                $q->where('class_id', $class->id);
            })
            ->with(['exam', 'answers.question'])
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = $this->grades->computeStudentClassGrade($student->id, $class->id);

        return view('instructor.gradebook.student', compact(
            'class',
            'student',
            'enrollment',
            'gradeItems',
            'summary',
            'quizAttempts',
            'examAttempts'
        ));
    }
}
