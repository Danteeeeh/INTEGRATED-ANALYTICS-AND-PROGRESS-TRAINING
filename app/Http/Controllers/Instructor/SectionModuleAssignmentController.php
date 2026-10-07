<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Module;
use App\Models\Notification;
use App\Models\SectionModuleAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionModuleAssignmentController extends Controller
{
    public function index(ClassModel $class): View
    {
        $this->authorize('view', $class);

        $sectionAssignments = SectionModuleAssignment::where('course_id', $class->course_id)
            ->where('section_id', $class->section_id)
            ->with(['section', 'module'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $modules = Module::where('course_id', $class->course_id)
            ->published()
            ->orderBy('position')
            ->get();

        return view('instructor.section_module_assignments.index', compact(
            'class',
            'sectionAssignments',
            'modules'
        ));
    }

    public function create(ClassModel $class): View
    {
        $this->authorize('update', $class);

        $modules = Module::where('course_id', $class->course_id)
            ->published()
            ->orderBy('position')
            ->get();

        return view('instructor.section_module_assignments.create', compact(
            'class',
            'modules'
        ));
    }

    public function store(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('update', $class);

        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
        ]);

        // Check if assignment already exists for this section
        $existing = SectionModuleAssignment::where('course_id', $class->course_id)
            ->where('section_id', $class->section_id)
            ->where('module_id', $validated['module_id'])
            ->first();

        if ($existing) {
            return redirect()->route('instructor.classes.section_module_assignments.index', $class)
                ->with('error', 'This module is already assigned to this section.');
        }

        $assignment = SectionModuleAssignment::create([
            'section_id' => $class->section_id,
            'module_id' => $validated['module_id'],
            'course_id' => $class->course_id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        // Create notifications for all students in this section
        $module = Module::find($validated['module_id']);
        $students = User::whereHas('enrollments', function ($q) use ($class) {
            $q->where('class_id', $class->id)->where('status', 'active');
        })->get();

        foreach ($students as $student) {
            Notification::create([
                'user_id' => $student->id,
                'type' => 'module_assigned',
                'title' => 'New Module Assigned',
                'body' => "A new module has been assigned to your section: {$module->title} in course: {$class->course->title}",
                'notifiable_type' => SectionModuleAssignment::class,
                'notifiable_id' => $assignment->id,
                'data' => [
                    'module_id' => $module->id,
                    'class_id' => $class->id,
                    'course_id' => $class->course_id,
                    'section_id' => $class->section_id,
                ],
                'link_url' => route('student.courses.modules.show', [$class->course, $module]),
                'channel' => 'in_app',
                'status' => 'unread',
                'is_read' => false,
            ]);
        }

        return redirect()->route('instructor.classes.section_module_assignments.index', $class)
            ->with('success', 'Module assigned to section successfully.');
    }

    public function destroy(ClassModel $class, SectionModuleAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $class);

        if ($assignment->course_id !== $class->course_id || $assignment->section_id !== $class->section_id) {
            abort(403);
        }

        $assignment->delete();

        return redirect()->route('instructor.classes.section_module_assignments.index', $class)
            ->with('success', 'Module assignment removed successfully.');
    }
}
