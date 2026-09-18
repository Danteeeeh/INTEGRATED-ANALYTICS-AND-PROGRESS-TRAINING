<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Enrollment::with(['student', 'class.course', 'class.academicPeriod']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

        $enrollments = $query->orderBy('enrolled_at', 'desc')->paginate(20);
        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.enrollments.index', compact('enrollments', 'students', 'classes'));
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
            'class_id' => 'required|exists:classes,id',
            'status' => 'required|in:pending,active,completed,dropped',
            'notes' => 'nullable|string',
        ]);

        // Check if student is already enrolled in this class
        $existing = Enrollment::where('student_id', $validated['student_id'])
            ->where('class_id', $validated['class_id'])
            ->where('status', '!=', 'dropped')
            ->first();

        if ($existing) {
            return back()->with('error', 'Student is already enrolled in this class.');
        }

        // Check class capacity
        $class = ClassModel::find($validated['class_id']);
        $currentEnrollments = Enrollment::where('class_id', $validated['class_id'])
            ->where('status', '!=', 'dropped')
            ->count();

        if ($currentEnrollments >= $class->max_students) {
            return back()->with('error', 'Class is already at full capacity.');
        }

        Enrollment::create($validated);

        return redirect()->route('admin.enrollments.index')
            ->with('status', 'Enrollment created successfully.');
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
            $exists = Enrollment::where('student_id', $item['student_id'])->where('class_id', $item['class_id'])->where('status', '!=', 'dropped')->exists();
            $class = ClassModel::find($item['class_id']);
            if (! $exists && $class && ! $class->isFull()) {
                Enrollment::create(array_merge($item, ['status' => 'pending', 'enrolled_at' => now()]));
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

    public function transfer(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate(['class_id' => ['required', 'exists:classes,id']]);
        $target = ClassModel::findOrFail($data['class_id']);
        if ($target->isFull()) {
            return back()->with('error', 'The target class is already at full capacity.');
        }
        if (Enrollment::where('student_id', $enrollment->student_id)->where('class_id', $target->id)->where('status', '!=', 'dropped')->exists()) {
            return back()->with('error', 'This student is already enrolled in the target class.');
        }

        $enrollment->update(['class_id' => $target->id]);

        return back()->with('status', 'Enrollment transferred successfully.');
    }
}
