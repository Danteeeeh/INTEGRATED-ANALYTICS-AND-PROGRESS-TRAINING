<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(): View
    {
        $classes = ClassModel::with(['course', 'instructor', 'enrollments'])
            ->orderBy('code')
            ->paginate(15);

        return view('registrar.classes.index', compact('classes'));
    }

    public function show(ClassModel $class): View
    {
        $class->load(['course', 'instructor', 'enrollments.student']);

        return view('registrar.classes.show', compact('class'));
    }

    /**
     * View all class schedules across the school, filterable by academic period.
     */
    public function schedules(Request $request): View
    {
        $query = ClassModel::with(['course', 'instructor', 'academicPeriod', 'enrollments'])
            ->orderBy('code');

        if ($request->filled('academic_period_id')) {
            $query->where('academic_period_id', $request->integer('academic_period_id'));
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->integer('instructor_id'));
        }

        $classes = $query->paginate(15)->withQueryString();
        $academicPeriods = \App\Models\AcademicPeriod::orderBy('start_date', 'desc')->get();
        $courses = \App\Models\Course::orderBy('title')->get();
        $instructors = \App\Models\User::whereHas('role', fn ($q) => $q->where('slug', \App\Models\Role::INSTRUCTOR))
            ->orderBy('first_name')
            ->get();

        return view('registrar.classes.schedules', compact('classes', 'academicPeriods', 'courses', 'instructors'));
    }
}
