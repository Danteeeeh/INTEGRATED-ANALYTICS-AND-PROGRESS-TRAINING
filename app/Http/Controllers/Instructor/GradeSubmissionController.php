<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\GradeSubmission;
use App\Services\GradeExtractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Instructor grade extraction & submission.
 *
 * Scope is enforced twice: the `role:instructor` middleware gates the routes,
 * and every action authorizes the class through ClassPolicy, so one instructor
 * can never read, export, or submit another instructor's gradebook (IDOR guard).
 *
 * Submitting records an LMS snapshot only — official final grades stay with AA.
 */
class GradeSubmissionController extends Controller
{
    public function __construct(private GradeExtractionService $extraction) {}

    /**
     * Grade submission history for this instructor's classes.
     */
    public function index(): View
    {
        $classes = ClassModel::where('instructor_id', auth()->id())
            ->with('course')
            ->withCount('enrollments')
            ->orderBy('code')
            ->get();

        $submissions = GradeSubmission::where('instructor_id', auth()->id())
            ->with(['class.course'])
            ->latest('id')
            ->paginate(15);

        return view('instructor.grade-submissions.index', compact('classes', 'submissions'));
    }

    /**
     * Extract + validate the LMS scores for a class (Preview Grade Export).
     */
    public function preview(ClassModel $class): View
    {
        $this->authorize('viewRoster', $class);

        $extraction = $this->extraction->preview($class);

        return view('instructor.grade-submissions.preview', [
            'class' => $class,
            'rows' => $extraction['rows'],
            'summary' => $extraction['summary'],
            'history' => GradeSubmission::where('class_id', $class->id)
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * CSV export of the extracted scores (does NOT submit anything).
     */
    public function export(ClassModel $class): StreamedResponse
    {
        $this->authorize('viewRoster', $class);

        $rows = $this->extraction->csvRows($class);

        $code = $class->code ?: $class->id;
        $filename = 'lms-grades-'.$code.'-'.now()->format('Ymd-His').'.csv';

        return Response::stream(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Record the submission. Never touches official records.
     */
    public function store(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('viewRoster', $class);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission = $this->extraction->submit($class, auth()->id(), $validated['notes'] ?? null);

        return redirect()
            ->route('instructor.grade-submissions.show', $submission)
            ->with('success', 'Grade submission recorded. Official final grades stay with AA — nothing was overwritten.');
    }

    /**
     * One submission's snapshot.
     */
    public function show(GradeSubmission $gradeSubmission): View
    {
        // A submission belongs to exactly one instructor; anyone else is
        // refused even when they guess the id.
        abort_unless($gradeSubmission->instructor_id === auth()->id(), 403);

        $gradeSubmission->load(['class.course', 'instructor', 'submittedBy']);

        return view('instructor.grade-submissions.show', ['submission' => $gradeSubmission]);
    }
}
