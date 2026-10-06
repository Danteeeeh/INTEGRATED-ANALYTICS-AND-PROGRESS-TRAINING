<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\SectionModuleAssignment;
use App\Models\StudentModuleAssignment;
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

        $modulesQuery = Module::published()
            ->ofCourse($course->id)
            ->with(['lessons' => function ($q) {
                $q->published()->orderBy('position', 'asc');
            }])
            ->with(['studentAssignments' => function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            }])
            ->orderBy('position', 'asc');

        $allModuleIds = (clone $modulesQuery)->pluck('id');

        // Prefer modules assigned to this student's section, but only when that
        // actually yields results. Stale section assignments (deleted modules,
        // another course) previously filtered everything out and the page
        // rendered empty even though the course had published modules.
        $sectionModuleIds = SectionModuleAssignment::active()
            ->byCourse($course->id)
            ->bySection($enrollment->class->section_id)
            ->whereIn('module_id', $allModuleIds)
            ->pluck('module_id');

        if ($sectionModuleIds->isNotEmpty()) {
            $modulesQuery->whereIn('id', $sectionModuleIds);
        }

        $modules = $modulesQuery->paginate(10)->withQueryString();

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

        // Create or get student-specific assignment for progress tracking
        $assignment = StudentModuleAssignment::byStudent($studentId)
            ->byClass($enrollment->class_id)
            ->where('module_id', $module->id)
            ->first();

        if (! $assignment) {
            $assignment = StudentModuleAssignment::create([
                'student_id' => $studentId,
                'module_id' => $module->id,
                'class_id' => $enrollment->class_id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);
        }

        // Mark as in progress if not already
        if ($assignment->status === 'assigned') {
            $assignment->markAsInProgress();
        }

        $module->load(['lessons' => function ($q) use ($studentId) {
            $q->published()
                ->with(['progress' => function ($q2) use ($studentId) {
                    $q2->where('student_id', $studentId);
                }])
                ->orderBy('position', 'asc');
        }]);

        return view('student.modules.show', compact('course', 'module', 'enrollment', 'assignment'));
    }
}
