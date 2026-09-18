<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
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
        $students->load('enrollments');

        return view('admin.students.index', compact('students'));
    }

    public function create(): View
    {
        $roles = Role::all();

        return view('admin.students.create', compact('roles'));
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

        User::create($validated);

        return redirect()->route('admin.students.index')
            ->with('status', 'Student created successfully.');
    }

    public function show(User $student): View
    {
        $student->load('role', 'enrollments.class.course', 'enrollments.class.instructor');

        return view('admin.students.show', compact('student'));
    }

    public function edit(User $student): View
    {
        $roles = Role::all();

        return view('admin.students.edit', compact('student', 'roles'));
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

        $student->update($validated);

        return redirect()->route('admin.students.index')
            ->with('status', 'Student updated successfully.');
    }

    public function destroy(User $student)
    {
        if ($student->enrollments()->exists()) {
            return back()->with('error', 'Cannot delete student with active enrollments.');
        }

        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('status', 'Student deleted successfully.');
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
