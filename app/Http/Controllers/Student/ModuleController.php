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
        //
        // No join to `modules` here: `whereIn` below already restricts to this
        // course's published modules, so the join filtered nothing extra and only
        // made `status`/`course_id` ambiguous in the WHERE clause. Ordering is
        // irrelevant anyway — this list is only ever fed back into `whereIn`,
        // and the final ordering comes from $modulesQuery.
        $sectionModuleIds = SectionModuleAssignment::active()
            ->byCourse($course->id)
            ->bySection($enrollment->class->section_id)
            ->whereIn('section_module_assignments.module_id', $allModuleIds)
            ->pluck('section_module_assignments.module_id');

        if ($sectionModuleIds->isNotEmpty()) {
            $modulesQuery->whereIn('id', $sectionModuleIds);
        }

        $modules = $modulesQuery->paginate(10)->withQueryString();

        return view('student.modules.index', compact('course', 'modules', 'enrollment'));
    }

    public function show(Course $course, Module $module): View
    {
        $studentId = auth()->id();

        // {course} and {module} are bound independently by primary key, so a
        // module from another course would otherwise render under this
        // course's header. That combination does not exist → 404.
        abort_unless((int) $module->course_id === (int) $course->id, 404);

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
