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

    public function export(Request $request): RedirectResponse|View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'virtual_class_id' => 'nullable|exists:virtual_classes,id',
            'student_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'format' => 'required|in:csv,xlsx,pdf',
        ]);

        $query = AttendanceRecord::with(['class.course', 'virtualClass', 'student', 'recordedBy']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('virtual_class_id')) {
            $query->where('virtual_class_id', $request->virtual_class_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }

        $records = $query->orderByDesc('attendance_date')->get();
        $format = $validated['format'];

        session()->flash('success', "Attendance export ({$format}) queued. {$records->count()} records will be included.");

        return back();
    }
}
