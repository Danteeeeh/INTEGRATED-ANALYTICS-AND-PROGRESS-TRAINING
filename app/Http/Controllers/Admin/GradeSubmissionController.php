<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\GradeSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super Admin grade-submission monitoring.
 *
 * Read-only oversight: see which classes have had LMS grades extracted and
 * submitted, and how complete each batch was. The Super Admin does not edit
 * official grades here — AA owns the official record, so there is no write
 * action and no path from this screen back into AA.
 */
class GradeSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $query = GradeSubmission::with(['class.course', 'instructor', 'submittedBy']);

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($classId = $request->integer('class_id')) {
            $query->where('class_id', $classId);
        }

        $submissions = $query->latest('id')->paginate(20)->withQueryString();

        $stats = [
            'total' => GradeSubmission::count(),
            'submitted' => GradeSubmission::where('status', GradeSubmission::STATUS_SUBMITTED)->count(),
            'classes' => GradeSubmission::distinct()->count('class_id'),
            'incomplete' => GradeSubmission::where('missing_count', '>', 0)->count(),
            'average_percent' => (float) (GradeSubmission::whereNotNull('average_percent')->avg('average_percent') ?? 0),
        ];

        $classes = ClassModel::with('course')->orderBy('code')->get();

        return view('admin.grade-submissions.index', compact('submissions', 'stats', 'classes', 'status'));
    }

    public function show(GradeSubmission $gradeSubmission): View
    {
        $gradeSubmission->load(['class.course', 'instructor', 'submittedBy']);

        return view('admin.grade-submissions.show', ['submission' => $gradeSubmission]);
    }
}
