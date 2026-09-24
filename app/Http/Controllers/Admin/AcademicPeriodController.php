<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicPeriodController extends Controller
{
    public function index()
    {
        $periods = AcademicPeriod::orderBy('start_date', 'desc')->get();

        return view('admin.academic_periods.index', compact('periods'));
    }

    public function create()
    {
        return view('admin.academic_periods.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:academic_periods,code',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'is_enrollment_open' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if ($request->boolean('is_current')) {
            AcademicPeriod::where('is_current', true)->update(['is_current' => false]);
        }

        AcademicPeriod::create($validated);

        return redirect()->route('admin.academic_periods.index')
            ->with('status', 'Academic period created successfully.');
    }

    public function show(AcademicPeriod $academicPeriod)
    {
        $academicPeriod->load('classes.course', 'classes.instructor');

        return view('admin.academic_periods.show', compact('academicPeriod'));
    }

    public function edit(AcademicPeriod $academicPeriod)
    {
        return view('admin.academic_periods.edit', compact('academicPeriod'));
    }

    public function update(Request $request, AcademicPeriod $academicPeriod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('academic_periods')->ignore($academicPeriod->id)],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'is_enrollment_open' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if ($request->boolean('is_current') && ! $academicPeriod->is_current) {
            AcademicPeriod::where('is_current', true)->update(['is_current' => false]);
        }

        $academicPeriod->update($validated);

        return redirect()->route('admin.academic_periods.index')
            ->with('status', 'Academic period updated successfully.');
    }

    public function destroy(AcademicPeriod $academicPeriod)
    {
        if ($academicPeriod->classes()->exists()) {
            return back()->with('error', 'Cannot delete academic period with associated classes.');
        }

        $academicPeriod->delete();

        return redirect()->route('admin.academic_periods.index')
            ->with('status', 'Academic period deleted successfully.');
    }

    public function setCurrent(AcademicPeriod $academicPeriod)
    {
        AcademicPeriod::where('is_current', true)->whereKeyNot($academicPeriod->id)->update(['is_current' => false]);
        $academicPeriod->update(['is_current' => true]);

        return back()->with('status', $academicPeriod->name.' is now the current academic period.');
    }
}
