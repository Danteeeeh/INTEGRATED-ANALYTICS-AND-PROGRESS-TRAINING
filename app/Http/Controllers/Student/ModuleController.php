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

        // Get modules assigned to this student's section
        $assignedModuleIds = SectionModuleAssignment::active()
            ->byCourse($course->id)
            ->bySection($enrollment->class->section_id)
            ->pluck('module_id');

        $modules = Module::published()
            ->ofCourse($course->id)
            ->whereIn('id', $assignedModuleIds)
            ->with(['lessons' => function ($q) {
                $q->published()->orderBy('position', 'asc');
            }])
            ->with(['studentAssignments' => function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
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

        // Check if module is assigned to this student's section
        $sectionAssignment = SectionModuleAssignment::active()
            ->byCourse($course->id)
            ->bySection($enrollment->class->section_id)
            ->where('module_id', $module->id)
            ->first();

        if (! $sectionAssignment) {
            abort(403, 'This module is not assigned to your section.');
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
