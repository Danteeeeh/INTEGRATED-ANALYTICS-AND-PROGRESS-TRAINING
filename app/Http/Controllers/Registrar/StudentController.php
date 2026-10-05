<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('role', 'enrollments')
            ->whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('registrar.students.index', compact('students'));
    }

    public function create(): View
    {
        $sections = \App\Models\Section::with('program')->orderBy('code')->get();
        return view('registrar.students.create', compact('sections'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'identifier' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'section_id' => ['nullable', 'exists:sections,id'],
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['role_id'] = Role::where('slug', Role::STUDENT)->firstOrFail()->id;
        $validated['status'] = 'active';

        $student = User::create($validated);

        // Auto-enroll student in classes for their section
        if ($student->section_id) {
            $classes = \App\Models\ClassModel::where('section_id', $student->section_id)
                ->where('status', 'active')
                ->get();

            foreach ($classes as $class) {
                // Check if class is full
                if ($class->isFull()) {
                    continue;
                }

                // Check if already enrolled
                $existing = \App\Models\Enrollment::where('student_id', $student->id)
                    ->where('class_id', $class->id)
                    ->where('status', '!=', 'dropped')
                    ->first();

                if (!$existing) {
                    \App\Models\Enrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ]);
                }
            }
        }

        return redirect()->route('registrar.students.index')
            ->with('status', 'Student record created and automatically enrolled in section classes.');
    }

    public function show(User $student): View
    {
        abort_unless($student->isStudent(), 404);
        $student->load('role', 'enrollments.class.course');

        return view('registrar.students.show', compact('student'));
    }

    public function edit(User $student): View
    {
        abort_unless($student->isStudent(), 404);

        return view('registrar.students.edit', compact('student'));
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$student->id],
            'identifier' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive,pending'],
        ]);

        $student->update($validated);

        return redirect()->route('registrar.students.index')
            ->with('status', 'Student record updated.');
    }
}
