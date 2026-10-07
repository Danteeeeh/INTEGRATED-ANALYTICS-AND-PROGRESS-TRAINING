<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Program::class);
        $programs = Program::with('department')->withCount('sections')->orderBy('code')->paginate(15);

        return view('admin.programs.index', compact('programs'));
    }

    public function create(): View
    {
        $this->authorize('create', Program::class);
        $departments = Department::orderBy('name')->get();

        return view('admin.programs.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:programs,code'],
            'description' => ['nullable', 'string'],
        ]);

        Program::create($validated);

        return redirect()->route('admin.programs.index')
            ->with('status', 'Program created successfully.');
    }

    public function show(Program $program): View
    {
        $this->authorize('view', $program);

        $program->load(['department', 'sections']);

        $courses = $program->courses()->orderBy('code')->get();
        $students = $program->students()->orderBy('last_name')->orderBy('first_name')->paginate(25);
        $instructors = $program->instructors()->orderBy('last_name')->orderBy('first_name')->get();

        return view('admin.programs.show', compact('program', 'courses', 'students', 'instructors'));
    }

    public function edit(Program $program): View
    {
        $this->authorize('update', $program);

        $departments = Department::orderBy('name')->get();

        return view('admin.programs.edit', compact('program', 'departments'));
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $this->authorize('update', $program);

        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:programs,code,'.$program->id],
            'description' => ['nullable', 'string'],
        ]);

        $program->update($validated);

        return redirect()->route('admin.programs.index')
            ->with('status', 'Program updated successfully.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        // Refuses when sections, courses or students still hang off it.
        $this->authorize('delete', $program);

        $program->delete();

        return redirect()->route('admin.programs.index')
            ->with('status', 'Program deleted.');
    }
}
