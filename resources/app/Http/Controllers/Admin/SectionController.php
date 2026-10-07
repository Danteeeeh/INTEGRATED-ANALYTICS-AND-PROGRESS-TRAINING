<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Program;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Section::class);
        $sections = Section::with(['program.department', 'academicPeriod'])
            ->withCount('classes')
            ->orderBy('code')
            ->paginate(15);

        return view('admin.sections.index', compact('sections'));
    }

    public function create(): View
    {
        $this->authorize('create', Section::class);
        $programs = Program::with('department')->orderBy('code')->get();
        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->get();

        return view('admin.sections.create', compact('programs', 'academicPeriods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['nullable', 'exists:programs,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:sections,code'],
            'max_students' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        Section::create($validated);

        return redirect()->route('admin.sections.index')
            ->with('status', 'Section created successfully.');
    }

    public function show(Section $section): View
    {
        $this->authorize('view', $section);

        $section->load(['program.department', 'academicPeriod', 'classes.course', 'classes.instructor']);

        $students = $section->students()
            ->with('role')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25);

        return view('admin.sections.show', compact('section', 'students'));
    }

    public function edit(Section $section): View
    {
        $this->authorize('update', $section);

        $programs = Program::with('department')->orderBy('code')->get();
        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->get();

        return view('admin.sections.edit', compact('section', 'programs', 'academicPeriods'));
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $this->authorize('update', $section);

        $validated = $request->validate([
            'program_id' => ['nullable', 'exists:programs,id'],
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:sections,code,'.$section->id],
            'max_students' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $section->update($validated);

        return redirect()->route('admin.sections.index')
            ->with('status', 'Section updated successfully.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        // Refuses when classes or students still hang off it.
        $this->authorize('delete', $section);

        $section->delete();

        return redirect()->route('admin.sections.index')
            ->with('status', 'Section deleted.');
    }
}
