<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $query = AttendanceRecord::with(['class.course', 'virtualClass', 'student', 'recordedBy']);

        if ($request->filled('course_id')) {
            $query->whereHas('class', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('virtual_class_id')) {
            $query->where('virtual_class_id', $request->virtual_class_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('attendance_date')) {
            $query->whereDate('attendance_date', $request->attendance_date);
        }

        if ($request->filled('week_number')) {
            $query->where('week_number', $request->week_number);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }

        $attendanceRecords = $query->orderByDesc('attendance_date')->orderBy('class_id')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $virtualClasses = VirtualClass::orderByDesc('meeting_date')->get(['id', 'title', 'meeting_date']);
        $students = User::whereHas('enrollments')->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.attendance.index', compact('attendanceRecords', 'courses', 'classes', 'virtualClasses', 'students'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AttendanceRecord::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $virtualClasses = VirtualClass::orderByDesc('meeting_date')->get(['id', 'title', 'meeting_date']);
        $preselectedClassId = $request->get('class_id');

        $students = collect();
        if ($preselectedClassId) {
            $class = ClassModel::find($preselectedClassId);
            if ($class) {
                $students = $class->students()->orderBy('users.last_name')->orderBy('users.first_name')->get(['users.id', 'users.first_name', 'users.last_name', 'users.email']);
            }
        } else {
            $students = User::whereHas('enrollments')->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);
        }

        return view('admin.attendance.create', compact('courses', 'classes', 'virtualClasses', 'students', 'preselectedClassId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AttendanceRecord::class);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'virtual_class_id' => 'nullable|exists:virtual_classes,id',
            'attendance_date' => 'required|date',
            'week_number' => 'nullable|integer|min:1',
            'session_title' => 'nullable|string|max:255',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
            'statuses' => 'required|array',
            'statuses.*' => 'in:present,late,absent,excused',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:500',
        ]);

        $created = 0;
        $recordedBy = $request->user()->id;

        foreach ($validated['student_ids'] as $index => $studentId) {
            AttendanceRecord::create([
                'class_id' => $validated['class_id'],
                'virtual_class_id' => $validated['virtual_class_id'],
                'attendance_date' => $validated['attendance_date'],
                'week_number' => $validated['week_number'] ?? null,
                'session_title' => $validated['session_title'],
                'student_id' => $studentId,
                'status' => $validated['statuses'][$index] ?? AttendanceRecord::STATUS_PRESENT,
                'notes' => $validated['notes'][$index] ?? null,
                'recorded_by' => $recordedBy,
            ]);
            $created++;
        }

        session()->flash('success', "Attendance recorded for {$created} student(s) successfully.");

        return redirect()->route('admin.attendance.index');
    }

    public function show(AttendanceRecord $attendanceRecord): View
    {
        $this->authorize('view', $attendanceRecord);

        $attendanceRecord->load(['class.course', 'virtualClass', 'student', 'recordedBy']);

        return view('admin.attendance.show', compact('attendanceRecord'));
    }

    public function edit(AttendanceRecord $attendanceRecord): View
    {
        $this->authorize('update', $attendanceRecord);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $virtualClasses = VirtualClass::orderByDesc('meeting_date')->get(['id', 'title', 'meeting_date']);
        $students = User::whereHas('enrollments')->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.attendance.edit', compact('attendanceRecord', 'courses', 'classes', 'virtualClasses', 'students'));
    }

    public function update(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'virtual_class_id' => 'nullable|exists:virtual_classes,id',
            'attendance_date' => 'required|date',
            'week_number' => 'nullable|integer|min:1',
            'session_title' => 'nullable|string|max:255',
            'student_id' => 'required|exists:users,id',
            'status' => 'required|in:present,late,absent,excused',
            'joined_at' => 'nullable|date',
            'left_at' => 'nullable|date|after_or_equal:joined_at',
            'duration_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $attendanceRecord->update($validated);

        session()->flash('success', 'Attendance record updated successfully.');

        return redirect()->route('admin.attendance.show', $attendanceRecord);
    }

    public function destroy(AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $this->authorize('delete', $attendanceRecord);

        try {
            $attendanceRecord->delete();
            session()->flash('success', 'Attendance record deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.attendance.index');
    }

    public function report(ClassModel $class): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $class->load(['course', 'instructor']);
        $records = AttendanceRecord::with('student')
            ->where('class_id', $class->id)
            ->orderBy('attendance_date', 'desc')
            ->get();
        $summary = [
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'excused' => $records->where('status', 'excused')->count(),
        ];

        return view('admin.attendance.report', compact('class', 'records', 'summary'));
    }

    /**
     * Download the attendance log as CSV.
     *
     * This used to only flash a "queued" message and bounce back to the list,
     * so the Export button produced no file at all. It now streams the rows that
     * the same filters would have shown on the index page, which is what the
     * Export link hands over via request()->query().
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'virtual_class_id' => 'nullable|exists:virtual_classes,id',
            'student_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:present,late,absent,excused',
            'attendance_date' => 'nullable|date',
            'week_number' => 'nullable|integer|min:1',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $filename = 'attendance-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($validated) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'Date', 'Week', 'Session', 'Class Code', 'Course', 'Student',
                'Student Identifier', 'Status', 'Joined At', 'Left At',
                'Duration (min)', 'Notes', 'Recorded By',
            ]);

            AttendanceRecord::with(['class.course', 'virtualClass', 'student', 'recordedBy'])
                ->when($validated['course_id'] ?? null, fn ($q, $courseId) => $q->whereHas('class', fn ($class) => $class->where('course_id', $courseId)))
                ->when($validated['class_id'] ?? null, fn ($q, $classId) => $q->where('class_id', $classId))
                ->when($validated['virtual_class_id'] ?? null, fn ($q, $virtualClassId) => $q->where('virtual_class_id', $virtualClassId))
                ->when($validated['student_id'] ?? null, fn ($q, $studentId) => $q->where('student_id', $studentId))
                ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->when($validated['attendance_date'] ?? null, fn ($q, $date) => $q->whereDate('attendance_date', $date))
                ->when($validated['week_number'] ?? null, fn ($q, $week) => $q->where('week_number', $week))
                ->when($validated['date_from'] ?? null, fn ($q, $from) => $q->whereDate('attendance_date', '>=', $from))
                ->when($validated['date_to'] ?? null, fn ($q, $to) => $q->whereDate('attendance_date', '<=', $to))
                ->orderByDesc('attendance_date')
                ->orderBy('class_id')
                ->chunk(config('lms.export_chunk_size'), function ($records) use ($handle) {
                    foreach ($records as $record) {
                        fputcsv($handle, [
                            $record->id,
                            $record->attendance_date?->toDateString(),
                            $record->week_number,
                            $record->session_title ?? $record->virtualClass?->title,
                            $record->class?->code,
                            $record->class?->course?->title,
                            $record->student?->full_name,
                            $record->student?->identifier,
                            $record->status,
                            $record->joined_at?->toDateTimeString(),
                            $record->left_at?->toDateTimeString(),
                            $record->duration_minutes,
                            $record->notes,
                            $record->recordedBy?->full_name,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
