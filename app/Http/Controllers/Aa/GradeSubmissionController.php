<?php

namespace App\Http\Controllers\Aa;

use App\Http\Controllers\Controller;
use App\Models\GradeSubmission;
use Illuminate\View\View;

/**
 * AA Staff read-only view of LMS grade-submission statuses.
 *
 * The Academic Officer may see which LMS grades instructors have submitted so
 * they can reconcile against the official record. This panel is deliberately
 * GET-only and never writes an official grade — AA remains the source of truth.
 */
class GradeSubmissionController extends Controller
{
    public function index(): View
    {
        $submissions = GradeSubmission::with(['class.course', 'instructor'])
            ->latest('id')
            ->paginate(20);

        $stats = [
            'total' => GradeSubmission::count(),
            'submitted' => GradeSubmission::where('status', GradeSubmission::STATUS_SUBMITTED)->count(),
            'incomplete' => GradeSubmission::where('missing_count', '>', 0)->count(),
        ];

        return view('aa.grade-submissions.index', compact('submissions', 'stats'));
    }
}
