<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ClassModel;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function __construct(private UserService $users) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $students = $this->users->getStudents($request->only(['search', 'status']));
        $students->load('enrollments', 'section.program', 'department', 'program');

        return view('admin.students.index', compact('students'));
    }

    public function create(): View
    {
        $roles = Role::assignable()->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $programs = Program::with('department')->orderBy('name')->get();
        $sections = Section::with('program')->orderBy('name')->get();

        return view('admin.students.create', compact('roles', 'departments', 'programs', 'sections'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        // Only hash password if provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = bcrypt($validated['password']);
        }

        // Set role from dropdown
        $validated['role_id'] = $request->input('role_id', Role::where('slug', Role::STUDENT)->first()->id);

        // Set status from dropdown
        $validated['status'] = $request->input('status', 'active');

        $student = User::create($validated);

        // Auto-enroll student in classes for their section
        if ($student->section_id) {
            $classes = ClassModel::where('section_id', $student->section_id)
                ->where('status', 'active')
                ->get();

            foreach ($classes as $class) {
                // Check if class is full
                if ($class->isFull()) {
                    continue;
                }

                // Check if already enrolled
                $existing = Enrollment::where('student_id', $student->id)
                    ->where('class_id', $class->id)
                    ->where('status', '!=', 'dropped')
                    ->first();

                if (!$existing) {
                    Enrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ]);
                }
            }
        }

        return redirect()->route('admin.students.index')
            ->with('status', 'Student created and automatically enrolled in section classes.');
    }

    public function show(User $student): View
    {
        $student->load('role', 'department', 'program', 'section', 'enrollments.class.course', 'enrollments.class.instructor', 'enrollments.class.academicPeriod');

        $availableClasses = ClassModel::with('course', 'instructor', 'academicPeriod', 'section')
            ->orderBy('code')
            ->get();
        $sections = Section::with('program.department')->orderBy('name')->get();

        return view('admin.students.show', compact('student', 'availableClasses', 'sections'));
    }

    public function edit(User $student): View
    {
        $roles = Role::assignable()->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $programs = Program::with('department')->orderBy('name')->get();
        $sections = Section::with('program')->orderBy('name')->get();

        return view('admin.students.edit', compact('student', 'roles', 'departments', 'programs', 'sections'));
    }

    public function update(UpdateUserRequest $request, User $student)
    {
        $validated = $request->validated();

        // Only update password if provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = bcrypt($validated['password']);
        }

        // Update role from dropdown
        $validated['role_id'] = $request->input('role_id', $student->role_id);

        // Update status from dropdown
        $validated['status'] = $request->input('status', $student->status);

        // Nullable hierarchy fields
        foreach (['department_id', 'program_id', 'section_id'] as $field) {
            $validated[$field] = $request->filled($field) ? $request->input($field) : null;
        }

        $oldSectionId = $student->section_id;
        $newSectionId = $validated['section_id'] ?? null;

        $student->update($validated);

        // Auto-enroll in new section's classes if section changed
        if ($newSectionId && $newSectionId !== $oldSectionId) {
            $classes = ClassModel::where('section_id', $newSectionId)
                ->where('status', 'active')
                ->get();

            foreach ($classes as $class) {
                // Check if class is full
                if ($class->isFull()) {
                    continue;
                }

                // Check if already enrolled
                $existing = Enrollment::where('student_id', $student->id)
                    ->where('class_id', $class->id)
                    ->where('status', '!=', 'dropped')
                    ->first();

                if (!$existing) {
                    Enrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ]);
                }
            }
        }

        return redirect()->route('admin.students.index')
            ->with('status', 'Student updated successfully.');
    }

    public function destroy(User $student)
    {
        $this->authorize('delete', $student);

        if ($student->enrollments()->exists()) {
            return back()->with('error', 'Cannot delete student with active enrollments.');
        }

        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('status', 'Student deleted successfully.');
    }

    public function assignClasses(Request $request, User $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
        ]);

        $classIds = array_map('intval', $validated['class_ids'] ?? []);

        DB::transaction(function () use ($student, $classIds) {
            // Add newly selected classes as active enrollments.
            $existing = Enrollment::where('student_id', $student->id)
                ->whereIn('class_id', $classIds)
                ->whereIn('status', ['active', 'pending'])
                ->pluck('class_id')
                ->all();

            foreach (array_diff($classIds, $existing) as $classId) {
                $class = ClassModel::find($classId);
                if (! $class) {
                    continue;
                }

                if ($class->capacity && $class->enrolled_count >= $class->capacity) {
                    continue;
                }

                // Re-activate a previously dropped enrollment instead of duplicating.
                $dropped = Enrollment::where('student_id', $student->id)
                    ->where('class_id', $classId)
                    ->where('status', 'dropped')
                    ->first();

                if ($dropped) {
                    $dropped->update([
                        'status' => 'active',
                        'enrolled_at' => now(),
                        'completed_at' => null,
                    ]);
                } else {
                    Enrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $classId,
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ]);
                }
            }

            // Remove selected classes that were un-checked (only active/pending ones).
            $removeIds = array_values(array_diff(
                Enrollment::where('student_id', $student->id)
                    ->whereIn('status', ['active', 'pending'])
                    ->pluck('class_id')
                    ->all(),
                $classIds
            ));

            if ($removeIds) {
                Enrollment::where('student_id', $student->id)
                    ->whereIn('class_id', $removeIds)
                    ->whereIn('status', ['active', 'pending'])
                    ->update(['status' => 'dropped']);
            }
        });

        return back()->with('status', 'Student class assignments updated successfully.');
    }

    public function assignSection(Request $request, User $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'section_id' => ['nullable', 'exists:sections,id'],
        ]);

        $newSectionId = $validated['section_id'] ?? null;
        $oldSectionId = $student->section_id;

        $student->update([
            'section_id' => $newSectionId,
            'program_id' => $newSectionId
                ? optional(Section::find($newSectionId)->program)->id
                : null,
            'department_id' => $newSectionId
                ? optional(Section::find($newSectionId)->program?->department)->id
                : null,
        ]);

        // Auto-enroll in new section's classes
        if ($newSectionId && $newSectionId !== $oldSectionId) {
            $classes = ClassModel::where('section_id', $newSectionId)
                ->where('status', 'active')
                ->get();

            foreach ($classes as $class) {
                // Check if class is full
                if ($class->isFull()) {
                    continue;
                }

                // Check if already enrolled
                $existing = Enrollment::where('student_id', $student->id)
                    ->where('class_id', $class->id)
                    ->where('status', '!=', 'dropped')
                    ->first();

                if (!$existing) {
                    Enrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ]);
                }
            }
        }

        return back()->with('status', 'Student section assignment updated and enrolled in section classes.');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('import', User::class);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $result = $this->users->importFromCsv($request->file('file')->getRealPath(), Role::STUDENT);
        $message = "Imported {$result['created']} student(s).";
        if ($result['skipped']) {
            $message .= " Skipped {$result['skipped']} invalid or duplicate row(s).";
        }

        return back()->with('status', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);
        $filename = 'students-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'First Name', 'Last Name', 'Email', 'Identifier', 'Status', 'Last Login']);
            User::with('role')->whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim((string) $request->input('search'));
                    $q->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('identifier', 'like', "%{$search}%");
                    });
                })
                ->orderBy('last_name')->orderBy('first_name')
                ->chunk(config('lms.export_chunk_size'), function ($students) use ($handle) {
                    foreach ($students as $student) {
                        fputcsv($handle, [$student->id, $student->first_name, $student->last_name, $student->email, $student->identifier, $student->status, optional($student->last_login_at)?->toDateTimeString()]);
                    }
                });
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
