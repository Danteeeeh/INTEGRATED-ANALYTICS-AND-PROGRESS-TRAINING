<?php

namespace App\Http\Controllers\Aa;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\GradeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

/**
 * Read-only LMS assessment reports for AA Staff.
 *
 * These are LMS activity/assessment reports (grade REFERENCE), not official
 * final grades — official grades remain in AA. AA Staff may view and export
 * them; no create/edit/delete is exposed.
 */
class ReportController extends Controller
{
    public function __construct(private GradeService $grades) {}

    public function index(): View
    {
        $courses = Course::withCount(['classes'])
            ->orderBy('title')
            ->get()
            ->map(function ($course) {
                $course->enrollments_count = Enrollment::whereHas('class', fn ($q) => $q->where('course_id', $course->id))
                    ->where('status', 'active')
                    ->count();

                return $course;
            });

        return view('aa.reports.index', compact('courses'));
    }

    public function show(Course $course): View
    {
        $rows = $this->courseRows($course);

        return view('aa.reports.show', compact('course', 'rows'));
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);

        $courses = $validated['course_id'] ?? null
            ? Course::whereKey($validated['course_id'])->get()
            : Course::orderBy('title')->get();

        $filename = 'aa_lms_assessment_report_'.now()->format('Y-m-d');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function () use ($courses) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Student ID', 'Student Name', 'Email', 'Course Code', 'Course Title', 'Class Code', 'Enrollment Status', 'LMS Score %', 'Letter Grade', 'Completed At']);

            foreach ($courses as $course) {
                foreach ($this->courseRows($course) as $row) {
                    fputcsv($file, [
                        $row['student_id'],
                        $row['student_name'],
                        $row['email'],
                        $course->code,
                        $course->title,
                        $row['class_code'],
                        $row['status'],
                        $row['score'],
                        $row['letter'],
                        $row['completed_at'],
                    ]);
                }
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Per-student rows for one course — real LMS data only (computed from
     * released, graded grade items). LMS scores, never official final grades.
     *
     * @return array<int, array<string, mixed>>
     */
    private function courseRows(Course $course): array
    {
        $rows = [];

        foreach ($course->classes()->with('enrollments.student')->get() as $class) {
            $summaries = $this->grades->computeClassGradeSummaries(
                $class->id,
                $class->enrollments->pluck('student_id')->all()
            );

            foreach ($class->enrollments as $enrollment) {
                $summary = $summaries[$enrollment->student_id] ?? null;

                $rows[] = [
                    'student_id' => $enrollment->student?->identifier ?? $enrollment->student_id,
                    'student_name' => $enrollment->student?->name ?? 'N/A',
                    'email' => $enrollment->student?->email ?? 'N/A',
                    'class_code' => $class->code,
                    'status' => $enrollment->status,
                    'score' => ($summary['is_graded'] ?? false) ? number_format($summary['percent'], 2) : '',
                    'letter' => ($summary['is_graded'] ?? false) ? $summary['letter_grade'] : '',
                    'completed_at' => $enrollment->completed_at?->format('Y-m-d') ?? '',
                ];
            }
        }

        return $rows;
    }
}
