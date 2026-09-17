<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(Course $course): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $modules = Module::published()
            ->ofCourse($course->id)
            ->with(['lessons' => function ($q) {
                $q->published()->orderBy('position', 'asc');
            }])
            ->orderBy('position', 'asc')
            ->paginate(10);

        return view('student.modules.index', compact('course', 'modules', 'enrollment'));
    }

    public function show(Course $course, Module $module): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $module->load(['lessons' => function ($q) use ($studentId) {
            $q->published()
                ->with(['progress' => function ($q2) use ($studentId) {
                    $q2->where('student_id', $studentId);
                }])
                ->orderBy('position', 'asc');
        }]);

        return view('student.modules.show', compact('course', 'module', 'enrollment'));
    }
}
