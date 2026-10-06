<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Department::class);
        $departments = Department::withCount('programs')->orderBy('name')->paginate(15);

        return view('admin.departments.index', compact('departments'));
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);
        return view('admin.departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
        ]);

        Department::create($validated);

        return redirect()->route('admin.departments.index')
            ->with('status', 'Department created successfully.');
    }

    public function show(Department $department): View
    {
        $this->authorize('view', $department);

        $department->load(['programs.sections']);

        $courses = $department->courses()->orderBy('code')->get();

        return view('admin.departments.show', compact('department', 'courses'));
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:departments,code,'.$department->id],
            'description' => ['nullable', 'string'],
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('status', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        // Refuses when programs, courses or students still hang off it.
        $this->authorize('delete', $department);

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('status', 'Department deleted.');
    }
}
