<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Enrollment::with(['student', 'class.course']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn ($student) => $student
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%"))
                    ->orWhereHas('class', fn ($class) => $class
                        ->where('code', 'like', "%{$search}%")
                        ->orWhereHas('course', fn ($course) => $course
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%")));
            });
        }

        $enrollments = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('registrar.enrollments.index', compact('enrollments'));
    }

    public function create(): View
    {
        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        $classes = ClassModel::with('course')->orderBy('code')->get();

        return view('registrar.enrollments.create', compact('students', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'class_id' => ['required', 'exists:classes,id'],
        ]);

        $student = User::findOrFail($validated['student_id']);
        abort_unless($student->isStudent(), 422);

        $existing = Enrollment::where('student_id', $validated['student_id'])
            ->where('class_id', $validated['class_id'])
            ->where('status', '!=', 'dropped')
            ->first();

        if ($existing) {
            return back()->with('error', 'Student is already enrolled in this class.');
        }

        Enrollment::create([
            'student_id' => $validated['student_id'],
            'class_id' => $validated['class_id'],
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        return redirect()->route('registrar.enrollments.index')
            ->with('status', 'Student enrolled successfully.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $enrollment->update(['status' => 'dropped']);

        return back()->with('status', 'Student removed from class.');
    }
}
