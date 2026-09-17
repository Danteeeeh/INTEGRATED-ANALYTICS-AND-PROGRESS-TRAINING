<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(ClassModel $class): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $attendanceRecords = AttendanceRecord::where('class_id', $class->id)
            ->with(['class', 'student', 'virtualClass'])
            ->orderBy('attendance_date', 'desc')
            ->paginate(20);

        return view('instructor.attendance.index', compact('class', 'attendanceRecords'));
    }

    public function create(ClassModel $class): View
    {
        $this->authorize('create', AttendanceRecord::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $class->load('enrollments.student');
        $students = $class->enrollments()->where('status', 'active')->with('student')->get();
        $statusOptions = [
            AttendanceRecord::STATUS_PRESENT => 'Present',
            AttendanceRecord::STATUS_LATE => 'Late',
            AttendanceRecord::STATUS_ABSENT => 'Absent',
            AttendanceRecord::STATUS_EXCUSED => 'Excused',
        ];

        return view('instructor.attendance.create', compact('class', 'students', 'statusOptions'));
    }

    public function store(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('create', AttendanceRecord::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'session_title' => 'nullable|string|max:255',
            'virtual_class_id' => 'nullable|exists:virtual_classes,id',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:users,id',
            'records.*.status' => 'required|string|in:present,late,absent,excused',
            'records.*.notes' => 'nullable|string',
        ]);

        foreach ($validated['records'] as $record) {
            AttendanceRecord::create([
                'class_id' => $class->id,
                'virtual_class_id' => $validated['virtual_class_id'] ?? null,
                'attendance_date' => $validated['attendance_date'],
                'session_title' => $validated['session_title'] ?? null,
                'student_id' => $record['student_id'],
                'status' => $record['status'],
                'notes' => $record['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);
        }

        return redirect()->route('instructor.classes.attendance.index', $class)
            ->with('success', 'Attendance recorded successfully.');
    }

    public function show(ClassModel $class, AttendanceRecord $attendance): View
    {
        $this->authorize('view', $attendance);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($attendance->class_id !== $class->id, 404);

        $attendance->load(['class', 'student', 'virtualClass', 'recordedBy']);

        $allRecords = AttendanceRecord::where('class_id', $class->id)
            ->where('attendance_date', $attendance->attendance_date)
            ->where('session_title', $attendance->session_title)
            ->with('student')
            ->get();

        return view('instructor.attendance.show', compact('class', 'attendance', 'allRecords'));
    }

    public function edit(ClassModel $class, AttendanceRecord $attendance): View
    {
        $this->authorize('update', $attendance);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($attendance->class_id !== $class->id, 404);

        $class->load('enrollments.student');
        $students = $class->enrollments()->where('status', 'active')->with('student')->get();
        $statusOptions = [
            AttendanceRecord::STATUS_PRESENT => 'Present',
            AttendanceRecord::STATUS_LATE => 'Late',
            AttendanceRecord::STATUS_ABSENT => 'Absent',
            AttendanceRecord::STATUS_EXCUSED => 'Excused',
        ];

        $attendance->load('student');

        $sessionRecords = AttendanceRecord::where('class_id', $class->id)
            ->where('attendance_date', $attendance->attendance_date)
            ->where('session_title', $attendance->session_title)
            ->with('student')
            ->get();

        return view('instructor.attendance.edit', compact('class', 'attendance', 'students', 'statusOptions', 'sessionRecords'));
    }

    public function update(Request $request, ClassModel $class, AttendanceRecord $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($attendance->class_id !== $class->id, 404);

        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'session_title' => 'nullable|string|max:255',
            'virtual_class_id' => 'nullable|exists:virtual_classes,id',
            'records' => 'required|array',
            'records.*.id' => 'nullable|exists:attendance_records,id',
            'records.*.student_id' => 'required|exists:users,id',
            'records.*.status' => 'required|string|in:present,late,absent,excused',
            'records.*.notes' => 'nullable|string',
        ]);

        foreach ($validated['records'] as $record) {
            if (isset($record['id'])) {
                AttendanceRecord::where('id', $record['id'])
                    ->where('class_id', $class->id)
                    ->update([
                        'status' => $record['status'],
                        'notes' => $record['notes'] ?? null,
                    ]);
            } else {
                AttendanceRecord::create([
                    'class_id' => $class->id,
                    'virtual_class_id' => $validated['virtual_class_id'] ?? null,
                    'attendance_date' => $validated['attendance_date'],
                    'session_title' => $validated['session_title'] ?? null,
                    'student_id' => $record['student_id'],
                    'status' => $record['status'],
                    'notes' => $record['notes'] ?? null,
                    'recorded_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('instructor.classes.attendance.index', $class)
            ->with('success', 'Attendance updated successfully.');
    }
}
