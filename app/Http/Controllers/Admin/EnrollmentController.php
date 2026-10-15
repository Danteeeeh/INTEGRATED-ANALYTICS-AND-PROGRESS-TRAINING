<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Enrollment records — READ-ONLY.
 *
 * AA is the authoritative source for enrollment, so this controller only
 * lists and displays records that arrived via "Sync from AA". The manual
 * create/edit/delete/bulk/approve/reject/drop/transfer/grade-encoding
 * endpoints were removed under the four-role architecture.
 */
class EnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Enrollment::with(['student', 'class.course', 'class.academicPeriod']);

        $includeDropped = $request->boolean('include_dropped');

        if ($request->filled('status')) {
            // An explicit status filter always wins, so ?status=dropped still
            // reaches the archived rows.
            $query->where('status', $request->status);
        } elseif (! $includeDropped) {
            // Dropped students were removed from the class on purpose, so they
            // should not dominate the default list. They stay in the database
            // and are reachable via the status filter or "Show Dropped".
            $query->where('enrollments.status', '!=', Enrollment::STATUS_DROPPED);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->orderBy('enrolled_at', 'desc')->paginate(20)->withQueryString();

        // Let the page say how many rows are being hidden, so a short list never
        // looks like data was lost.
        $droppedCount = Enrollment::where('status', Enrollment::STATUS_DROPPED)->count();

        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.enrollments.index', compact('enrollments', 'students', 'classes', 'includeDropped', 'droppedCount'));
    }

    public function show(Enrollment $enrollment): View
    {
        $enrollment->load(['student', 'class.course', 'class.academicPeriod', 'class.instructor']);

        return view('admin.enrollments.show', compact('enrollment'));
    }
}
