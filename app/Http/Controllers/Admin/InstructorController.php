<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ClassModel;
use App\Models\Department;
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

class InstructorController extends Controller
{
    public function __construct(private UserService $users) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $instructors = $this->users->getInstructors($request->only(['search', 'status']));
        $instructors->load('department', 'classesInstructing');

        return view('admin.instructors.index', compact('instructors'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = Role::where('slug', Role::INSTRUCTOR)->get();
        $departments = Department::orderBy('name')->get();
        $programs = Program::with('department')->orderBy('name')->get();
        $sections = Section::with('program')->orderBy('name')->get();

        return view('admin.instructors.create', compact('roles', 'departments', 'programs', 'sections'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['role_id'] = Role::where('slug', Role::INSTRUCTOR)->value('id');
        $this->users->createUser($data);

        return redirect()->route('admin.instructors.index')->with('status', 'Instructor created successfully.');
    }

    public function show(User $instructor): View
    {
        $this->authorize('view', $instructor);

        $instructor->load('role', 'department', 'program', 'section', 'classesInstructing.course', 'classesInstructing.academicPeriod', 'classesInstructing.section');

        $availableClasses = ClassModel::with('course', 'instructor', 'academicPeriod', 'section')
            ->orderBy('code')
            ->get();
        $sections = Section::with('program.department')->orderBy('name')->get();

        return view('admin.instructors.show', compact('instructor', 'availableClasses', 'sections'));
    }

    public function edit(User $instructor): View
    {
        $this->authorize('update', $instructor);

        $roles = Role::assignable()->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $programs = Program::with('department')->orderBy('name')->get();
        $sections = Section::with('program')->orderBy('name')->get();

        return view('admin.instructors.edit', compact('instructor', 'roles', 'departments', 'programs', 'sections'));
    }

    public function update(UpdateUserRequest $request, User $instructor): RedirectResponse
    {
        $this->users->updateUser($instructor->id, $request->validated());

        return redirect()->route('admin.instructors.index')->with('status', 'Instructor updated successfully.');
    }

    public function destroy(User $instructor): RedirectResponse
    {
        $this->authorize('delete', $instructor);

        $this->users->deactivateUser($instructor->id);

        return redirect()->route('admin.instructors.index')->with('status', 'Instructor deactivated.');
    }

    public function assignClasses(Request $request, User $instructor): RedirectResponse
    {
        $this->authorize('update', $instructor);

        $validated = $request->validate([
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
        ]);

        $classIds = array_map('intval', $validated['class_ids'] ?? []);

        DB::transaction(function () use ($instructor, $classIds) {
            // Assign newly selected classes to this instructor.
            // NB: in MySQL, `instructor_id != X` excludes NULL rows, so unassigned classes must be matched explicitly.
            ClassModel::whereIn('id', $classIds)
                ->where(function ($q) use ($instructor) {
                    $q->where('instructor_id', '!=', $instructor->id)
                        ->orWhereNull('instructor_id');
                })
                ->update(['instructor_id' => $instructor->id]);

            // Unassign classes that were un-checked (and still belong to this instructor).
            ClassModel::where('instructor_id', $instructor->id)
                ->whereNotIn('id', $classIds)
                ->update(['instructor_id' => null]);
        });

        return back()->with('status', 'Instructor class assignments updated successfully.');
    }

    public function assignSection(Request $request, User $instructor): RedirectResponse
    {
        $this->authorize('update', $instructor);

        $validated = $request->validate([
            'section_id' => ['nullable', 'exists:sections,id'],
        ]);

        $instructor->update([
            'section_id' => $validated['section_id'] ?? null,
            'program_id' => $validated['section_id']
                ? optional(Section::find($validated['section_id'])->program)->id
                : null,
            'department_id' => $validated['section_id']
                ? optional(Section::find($validated['section_id'])->program?->department)->id
                : null,
        ]);

        return back()->with('status', 'Instructor section assignment updated.');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('import', User::class);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $result = $this->users->importFromCsv($request->file('file')->getRealPath(), Role::INSTRUCTOR);
        $message = "Imported {$result['created']} instructor(s).";
        if ($result['skipped']) {
            $message .= " Skipped {$result['skipped']} invalid or duplicate row(s).";
        }

        return back()->with('status', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);
        $filename = 'instructors-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'First Name', 'Last Name', 'Email', 'Identifier', 'Status', 'Last Login']);
            User::with('role')->whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))
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
                ->chunk(config('lms.export_chunk_size'), function ($instructors) use ($handle) {
                    foreach ($instructors as $instructor) {
                        fputcsv($handle, [$instructor->id, $instructor->first_name, $instructor->last_name, $instructor->email, $instructor->identifier, $instructor->status, optional($instructor->last_login_at)?->toDateTimeString()]);
                    }
                });
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
