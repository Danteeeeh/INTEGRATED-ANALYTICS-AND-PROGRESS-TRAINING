<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::withCount('classes')->orderBy('title')->paginate(15);

        return view('registrar.courses.index', compact('courses'));
    }

    public function show(Course $course): View
    {
        $course->load('classes.instructor', 'classes.enrollments');

        return view('registrar.courses.show', compact('course'));
    }
}
