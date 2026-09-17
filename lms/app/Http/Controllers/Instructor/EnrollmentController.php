<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        return view('instructor.enrollments.index', compact('enrollments', 'classes'));
    }

    public function create(): View
    {
        $instructorId = auth()->id();
        $classes = ClassModel::where('instructor_id', $instructorId)
            ->with('course')
            ->active()
            ->orderBy('code')
            ->get();

        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('instructor.enrollments.create', compact('classes', 'students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'status' => 'required|in:pending,active,completed,dropped',
            'notes' => 'nullable|string',
        ]);

        // Verify the class belongs to the instructor
        $class = ClassModel::find($validated['class_id']);
        if ($class->instructor_id !== auth()->id()) {
            return back()->with('error', 'You can only enroll students in your own classes.');
        }

        // Check if student is already enrolled in this class
        $existing = Enrollment::where('student_id', $validated['student_id'])
            ->where('class_id', $validated['class_id'])
            ->where('status', '!=', 'dropped')
            ->first();

        if ($existing) {
            return back()->with('error', 'Student is already enrolled in this class.');
        }

        // Check class capacity
        $currentEnrollments = Enrollment::where('class_id', $validated['class_id'])
            ->where('status', '!=', 'dropped')
            ->count();

        if ($currentEnrollments >= $class->max_students) {
            return back()->with('error', 'Class is already at full capacity.');
        }

        Enrollment::create($validated);

        return redirect()->route('instructor.enrollments.index')
            ->with('status', 'Student enrolled successfully.');
    }

    public function show(Enrollment $enrollment): View
    {
        // Verify the enrollment belongs to instructor's class
        if ($enrollment->class->instructor_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }

        $enrollment->load(['student', 'class.course', 'class.academicPeriod']);

        // Grades of this student in this class
        $grades = Grade::where('student_id', $enrollment->student_id)
            ->whereHas('item', fn ($q) => $q->where('class_id', $enrollment->class_id))
            ->with('item')
            ->orderBy('graded_at', 'desc')
            ->get();

        $averageGrade = $grades->whereNotNull('score_percent')->avg('score_percent');

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

        return view('instructor.enrollments.show', compact('enrollment', 'grades', 'averageGrade', 'attendance', 'attendanceSummary', 'attendanceRate'));
    }

    /**
     * Mark an enrollment as completed (optionally with a final grade).
     */
    public function complete(Request $request, Enrollment $enrollment)
    {
        if ($enrollment->class->instructor_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'final_grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $enrollment->markAsCompleted($validated['final_grade'] ?? null);

        return redirect()->route('instructor.enrollments.show', $enrollment)
            ->with('status', 'Enrollment marked as completed.');
    }

    /**
     * Drop an enrollment.
     */
    public function drop(Enrollment $enrollment)
    {
        if ($enrollment->class->instructor_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }

        $enrollment->drop();

        return redirect()->route('instructor.enrollments.show', $enrollment)
            ->with('status', 'Enrollment dropped.');
    }

    /**
     * Re-activate a dropped/pending enrollment.
     */
    public function activate(Enrollment $enrollment)
    {
        if ($enrollment->class->instructor_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }

        $enrollment->update(['status' => 'active']);

        return redirect()->route('instructor.enrollments.show', $enrollment)
            ->with('status', 'Enrollment re-activated.');
    }

    public function edit(Enrollment $enrollment): View
    {
        // Verify the enrollment belongs to instructor's class
        if ($enrollment->class->instructor_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }

        $enrollment->load(['student', 'class']);

        return view('instructor.enrollments.edit', compact('enrollment'));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        // Verify the enrollment belongs to instructor's class
        if ($enrollment->class->instructor_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,active,completed,dropped',
            'final_grade' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'completed' && isset($validated['final_grade'])) {
            $validated['completed_at'] = now();
        }

        $enrollment->update($validated);

        return redirect()->route('instructor.enrollments.index')
            ->with('status', 'Enrollment updated successfully.');
    }

    public function destroy(Enrollment $enrollment)
    {
        // Verify the enrollment belongs to instructor's class
        if ($enrollment->class->instructor_id !== auth()->id()) {
            abort(403, 'Unauthorized access.');
        }

        $enrollment->delete();

        return redirect()->route('instructor.enrollments.index')
            ->with('status', 'Enrollment removed successfully.');
    }
}
