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
        $programs = Program::with('department')->withCount('sections')->orderBy('code')->paginate(15);

        return view('admin.programs.index', compact('programs'));
    }

    public function create(): View
    {
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
        $program->load(['department', 'sections']);

        return view('admin.programs.show', compact('program'));
    }

    public function edit(Program $program): View
    {
        $departments = Department::orderBy('name')->get();

        return view('admin.programs.edit', compact('program', 'departments'));
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
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
        $program->delete();

        return redirect()->route('admin.programs.index')
            ->with('status', 'Program deleted.');
    }
}
