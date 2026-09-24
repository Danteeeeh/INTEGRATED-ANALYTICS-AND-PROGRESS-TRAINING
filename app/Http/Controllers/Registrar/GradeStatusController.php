<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Registrar (Staff) grade-status oversight: view submitted grades per class,
 * check completion/status, and return grades to the instructor for correction.
 * Registrar never edits grade values directly — that stays with instructors.
 */
class GradeStatusController extends Controller
{
    public function index(Request $request): View
    {
        $query = ClassModel::with(['course', 'instructor'])
            ->withCount([
                'enrollments as active_enrollments' => fn ($q) => $q->where('status', 'active'),
                'enrollments as graded_enrollments' => fn ($q) => $q->where('status', '!=', 'dropped')->whereNotNull('final_grade'),
            ])
            ->orderBy('code');

        if ($request->filled('academic_period_id')) {
            $query->where('academic_period_id', $request->integer('academic_period_id'));
        }

        $classes = $query->paginate(15)->withQueryString();
        $academicPeriods = \App\Models\AcademicPeriod::orderBy('start_date', 'desc')->get();

        return view('registrar.grades.index', compact('classes', 'academicPeriods'));
    }

    public function classGrades(ClassModel $class): View
    {
        $class->load(['course', 'instructor', 'enrollments.student']);

        return view('registrar.grades.class', compact('class'));
    }

    /**
     * Flag the class's grades as returned for correction: reset final grades
     * on active enrollments so the instructor knows they need review.
     */
    public function returnForCorrection(ClassModel $class): RedirectResponse
    {
        $enrollments = Enrollment::where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->whereNotNull('final_grade')
            ->get();

        foreach ($enrollments as $enrollment) {
            $enrollment->update([
                'final_grade' => null,
                'notes' => trim(($enrollment->notes ?? '')."\nGrades returned for correction on ".now()->toDateTimeString()),
            ]);
        }

        return back()->with('status', "Grades for {$class->code} were returned for correction.");
    }
}
