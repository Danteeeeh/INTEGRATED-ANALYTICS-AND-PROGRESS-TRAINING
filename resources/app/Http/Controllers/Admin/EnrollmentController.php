<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\EnrollmentLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

    public function create(): View
    {
        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        $classes = ClassModel::with('course')->active()->orderBy('code')->get();

        return view('admin.enrollments.create', compact('students', 'classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'required|exists:classes,id',
            'status' => 'required|in:pending,active,completed,dropped',
            'notes' => 'nullable|string',
        ]);

        $created = 0;
        $revivedCount = 0;
        $errors = [];

        foreach ($validated['class_ids'] as $classId) {
            $existing = Enrollment::where('student_id', $validated['student_id'])
                ->where('class_id', $classId)
                ->where('status', '!=', 'dropped')
                ->first();

            if ($existing) {
                $errors[] = "Student is already enrolled in class #{$classId}.";

                continue;
            }

            $class = ClassModel::find($classId);

            if ($class && $class->isFull()) {
                $errors[] = "Class {$class->code} is already at full capacity.";

                continue;
            }

            // A dropped row for the same student+class is revived rather than
            // inserted: enrollments is UNIQUE(student_id, class_id).
            [$enrollment, $revived] = Enrollment::enroll(
                (int) $validated['student_id'],
                (int) $classId,
                $validated['status'],
                $validated['notes'] ?? null,
            );

            if ($enrollment === null) {
                $errors[] = "Student is already enrolled in class #{$classId}.";

                continue;
            }

            $created++;
            $revivedCount += $revived ? 1 : 0;
        }

        $message = $created > 0 ? "{$created} enrollment(s) created successfully." : 'No enrollments were created.';

        if ($revivedCount > 0) {
            $message .= " {$revivedCount} previous drop(s) were reactivated.";
        }

        // 'errors' is reserved by Laravel for the ViewErrorBag. Flashing a plain array
        // under that key shadows real validation errors and makes $errors->any()
        // fatal wherever a layout reads it, so use a distinct name.
        return redirect()->route('admin.enrollments.index')
            ->with('status', $message)
            ->with('enrollment_errors', $errors);
    }

    public function show(Enrollment $enrollment): View
    {
        $enrollment->load(['student', 'class.course', 'class.academicPeriod', 'class.instructor']);

        return view('admin.enrollments.show', compact('enrollment'));
    }

    public function edit(Enrollment $enrollment): View
    {
        $enrollment->load(['student', 'class']);
        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        $classes = ClassModel::with('course')->active()->orderBy('code')->get();

        return view('admin.enrollments.edit', compact('enrollment', 'students', 'classes'));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,active,completed,dropped',
            'final_grade' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'completed' && isset($validated['final_grade'])) {
            $validated['completed_at'] = now();
        }

        $enrollment->update($validated);

        return redirect()->route('admin.enrollments.index')
            ->with('status', 'Enrollment updated successfully.');
    }

    public function destroy(Enrollment $enrollment)
    {
        $this->authorize('delete', $enrollment);

        // Enrollment is a hard delete — there are no SoftDeletes on the model.
        // Grades, lesson progress and attendance are keyed on
        // (student_id, class_id) rather than enrollment_id, so they survive on
        // purpose: the history stays, only the roster entry goes.
        $enrollment->delete();

        return redirect()->route('admin.enrollments.index')
            ->with('status', 'Enrollment deleted successfully.');
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'enrollments' => ['required', 'array', 'min:1'],
            'enrollments.*.student_id' => ['required', 'exists:users,id'],
            'enrollments.*.class_id' => ['required', 'exists:classes,id'],
        ]);

        $created = 0;
        foreach ($data['enrollments'] as $item) {
            $class = ClassModel::find($item['class_id']);

            if ($class && $class->isFull()) {
                continue;
            }

            // Revives a dropped row instead of violating UNIQUE(student_id, class_id).
            [$enrollment] = Enrollment::enroll(
                (int) $item['student_id'],
                (int) $item['class_id'],
                Enrollment::STATUS_PENDING,
            );

            if ($enrollment !== null) {
                $created++;
            }
        }

        return back()->with('status', $created.' enrollment(s) added. Existing or full-class entries were skipped.');
    }

    public function approve(Enrollment $enrollment)
    {
        $enrollment->update(['status' => 'active']);

        return back()->with('status', 'Enrollment approved.');
    }

    public function reject(Enrollment $enrollment)
    {
        $enrollment->update(['status' => 'dropped']);

        return back()->with('status', 'Enrollment request rejected.');
    }

    public function drop(Enrollment $enrollment)
    {
        $enrollment->drop();

        return back()->with('status', 'Enrollment dropped.');
    }

    public function transferForm(Enrollment $enrollment): View
    {
        $enrollment->load(['student', 'class.course']);
        $classes = ClassModel::with(['course', 'instructor'])
            ->where('id', '!=', $enrollment->class_id)
            ->where('status', 'active')
            ->orderBy('code')
            ->get();

        return view('admin.enrollments.transfer', compact('enrollment', 'classes'));
    }

    public function transfer(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate(['class_id' => ['required', 'exists:classes,id']]);
        $targetClass = ClassModel::findOrFail($validated['class_id']);

        if ($targetClass->id === $enrollment->class_id) {
            return back()->with('error', 'Student is already in that class/section.');
        }

        if ($targetClass->isFull()) {
            return back()->with('error', 'The target class is already at full capacity.');
        }

        $duplicate = Enrollment::where('student_id', $enrollment->student_id)
            ->where('class_id', $targetClass->id)
            ->where('status', '!=', 'dropped')
            ->exists();

        if ($duplicate) {
            return back()->with('error', 'Student is already enrolled in the target class.');
        }

        $previousCode = $enrollment->class?->code;
        $enrollment->update([
            'class_id' => $targetClass->id,
            'status' => 'active',
            'notes' => trim(($enrollment->notes ?? '')."\nTransferred from {$previousCode} on ".now()->toDateTimeString()),
            'completed_at' => null,
        ]);

        return back()->with('status', "Student transferred to {$targetClass->code} successfully.");
    }

    public function activate(Enrollment $enrollment)
    {
        $enrollment->update(['status' => 'active', 'completed_at' => null]);

        return back()->with('status', 'Enrollment activated.');
    }

    public function deactivate(Enrollment $enrollment)
    {
        $enrollment->update(['status' => 'dropped']);

        return back()->with('status', 'Enrollment deactivated.');
    }

    public function bulkAutoEnroll(Request $request)
    {
        $validated = $request->validate([
            'section_id' => ['nullable', 'exists:sections,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'status' => ['nullable', 'in:pending,active'],
            'preview' => ['nullable', 'boolean'],
        ]);

        $sectionId = $validated['section_id'] ?? null;
        $classId = $validated['class_id'] ?? null;
        $courseId = $validated['course_id'] ?? null;
        $programId = $validated['program_id'] ?? null;
        $departmentId = $validated['department_id'] ?? null;
        $academicPeriodId = $validated['academic_period_id'] ?? null;
        $status = $validated['status'] ?? 'active';
        $isPreview = $validated['preview'] ?? false;

        if (!$sectionId && !$classId && !$courseId && !$programId && !$departmentId && !$academicPeriodId) {
            return back()->with('error', 'Please select at least one filter (section, class, course, program, department, or academic period).');
        }

        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->where('status', 'active')
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($departmentId, function ($q) use ($programId) {
                if (!$programId) {
                    $q->whereHas('program', fn ($pq) => $pq->where('department_id', request('department_id')));
                }
            })
            ->get();

        $classesToEnroll = collect();

        if ($classId) {
            $classesToEnroll->push(ClassModel::find($classId));
        } elseif ($courseId) {
            $classesToEnroll = ClassModel::where('course_id', $courseId)
                ->where('status', 'active')
                ->when($academicPeriodId, fn ($q) => $q->where('academic_period_id', $academicPeriodId))
                ->get();
        } elseif ($sectionId) {
            $classesToEnroll = ClassModel::where('section_id', $sectionId)
                ->where('status', 'active')
                ->when($academicPeriodId, fn ($q) => $q->where('academic_period_id', $academicPeriodId))
                ->get();
        } elseif ($programId) {
            $classesToEnroll = ClassModel::whereHas('course', fn ($q) => $q->where('program_id', $programId))
                ->where('status', 'active')
                ->when($academicPeriodId, fn ($q) => $q->where('academic_period_id', $academicPeriodId))
                ->get();
        } elseif ($departmentId) {
            $classesToEnroll = ClassModel::whereHas('course', fn ($q) => $q->whereHas('program', fn ($pq) => $pq->where('department_id', $departmentId)))
                ->where('status', 'active')
                ->when($academicPeriodId, fn ($q) => $q->where('academic_period_id', $academicPeriodId))
                ->get();
        } elseif ($academicPeriodId) {
            $classesToEnroll = ClassModel::where('academic_period_id', $academicPeriodId)
                ->where('status', 'active')
                ->get();
        }

        if ($isPreview) {
            $previewData = [];
            foreach ($students as $student) {
                foreach ($classesToEnroll as $class) {
                    if (!$class) continue;

                    $existing = Enrollment::where('student_id', $student->id)
                        ->where('class_id', $class->id)
                        ->where('status', '!=', 'dropped')
                        ->first();

                    $previewData[] = [
                        'student' => $student->full_name,
                        'student_id' => $student->id,
                        'class' => $class->code,
                        'class_id' => $class->id,
                        'course' => $class->course?->title ?? 'N/A',
                        'is_full' => $class->isFull(),
                        'already_enrolled' => $existing !== null,
                        'can_enroll' => !$class->isFull() && $existing === null,
                    ];
                }
            }

            return back()->with('preview_data', $previewData)->with('status', 'Preview generated. Review the list below before confirming.');
        }

        $created = 0;
        $skipped = 0;
        $skippedFull = 0;
        $skippedExisting = 0;
        $revivedCount = 0;
        $filters = [
            'section_id' => $sectionId,
            'class_id' => $classId,
            'course_id' => $courseId,
            'program_id' => $programId,
            'department_id' => $departmentId,
            'academic_period_id' => $academicPeriodId,
        ];

        foreach ($students as $student) {
            foreach ($classesToEnroll as $class) {
                if (!$class) {
                    continue;
                }

                // Check if class is full
                if ($class->isFull()) {
                    $skippedFull++;
                    EnrollmentLog::create([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'performed_by' => Auth::id(),
                        'action' => 'auto_enroll',
                        'details' => "Skipped: Class is full",
                        'filters' => $filters,
                        'status' => 'failed',
                        'error_message' => 'Class capacity reached',
                    ]);
                    continue;
                }

                // Check if already enrolled
                $existing = Enrollment::where('student_id', $student->id)
                    ->where('class_id', $class->id)
                    ->where('status', '!=', 'dropped')
                    ->first();

                if ($existing) {
                    $skippedExisting++;
                    EnrollmentLog::create([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'enrollment_id' => $existing->id,
                        'performed_by' => Auth::id(),
                        'action' => 'auto_enroll',
                        'details' => "Skipped: Already enrolled",
                        'filters' => $filters,
                        'status' => 'failed',
                        'error_message' => 'Student already enrolled in this class',
                    ]);
                    continue;
                }

                // The student may have dropped this class before, which enroll() revives
                // in place — inserting a second row would violate the
                // UNIQUE(student_id, class_id) index.
                [$enrollment, $wasRevived] = Enrollment::enroll($student->id, $class->id, $status);

                if ($enrollment === null) {
                    $skippedExisting++;

                    continue;
                }

                EnrollmentLog::create([
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'performed_by' => Auth::id(),
                    'action' => 'auto_enroll',
                    'details' => $wasRevived
                        ? "Re-activated a dropped enrollment in class {$class->code}"
                        : "Auto-enrolled student in class {$class->code}",
                    'filters' => $filters,
                    'status' => 'success',
                ]);

                $created++;
                $revivedCount += $wasRevived ? 1 : 0;
            }
        }

        $message = "Successfully enrolled {$created} student(s).";
        if ($revivedCount > 0) {
            $message .= " {$revivedCount} previous drop(s) were reactivated.";
        }
        if ($skippedFull > 0) {
            $message .= " Skipped {$skippedFull} due to full classes.";
        }
        if ($skippedExisting > 0) {
            $message .= " Skipped {$skippedExisting} already enrolled.";
        }
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} for other reasons.";
        }

        return back()->with('status', $message);
    }
}
