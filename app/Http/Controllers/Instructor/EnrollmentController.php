<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Services\GradeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Enrollment records — READ-ONLY for faculty.
 *
 * Enrollment arrives via the Super Admin's "Sync from AA" (AA = source of
 * truth). Faculty may view their own class rosters and per-student records;
 * create/edit/delete/status/grade-encoding endpoints were removed.
 */
class EnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $instructorId = auth()->id();
        $query = Enrollment::whereHas('class', fn ($q) => $q->where('instructor_id', $instructorId))
            ->with(['student', 'class.course', 'class.academicPeriod']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->whereHas('student', fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $enrollments = $query->orderBy('enrolled_at', 'desc')->paginate(20);
        $classes = ClassModel::where('instructor_id', $instructorId)->with('course')->orderBy('code')->get();

        $gradeSummaries = app(GradeService::class)->computeSummariesForEnrollments($enrollments);

        return view('instructor.enrollments.index', compact('enrollments', 'classes', 'gradeSummaries'));
    }

    public function show(Enrollment $enrollment): View
    {
        // Verify the enrollment belongs to instructor's class
        if ($enrollment->class->instructor_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }

        $enrollment->load(['student', 'class.course', 'class.academicPeriod']);

        // LMS assessment grades of this student in this class (LMS scores only —
        // official grades live in AA).
        $grades = Grade::where('student_id', $enrollment->student_id)
            ->whereHas('item', fn ($q) => $q->where('class_id', $enrollment->class_id))
            ->with('item')
            ->orderBy('graded_at', 'desc')
            ->get();

        // Same computation as the gradebook and Performance Analytics.
        $gradeSummary = app(GradeService::class)->computeStudentClassGrade(
            $enrollment->student_id,
            $enrollment->class_id
        );

        // Attendance of this student in this class
        $attendance = AttendanceRecord::where('student_id', $enrollment->student_id)
            ->where('class_id', $enrollment->class_id)
            ->get();

        $attendanceSummary = [
            'total' => $attendance->count(),
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
        ];
        $attendanceRate = $attendanceSummary['total'] > 0
            ? round(($attendanceSummary['present'] / $attendanceSummary['total']) * 100, 1)
            : null;

        return view('instructor.enrollments.show', compact(
            'enrollment',
            'grades',
            'gradeSummary',
            'attendance',
            'attendanceSummary',
            'attendanceRate'
        ));
    }
}
