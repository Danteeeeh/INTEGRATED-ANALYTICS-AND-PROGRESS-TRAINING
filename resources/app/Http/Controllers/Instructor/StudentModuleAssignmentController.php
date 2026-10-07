<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Module;
use App\Models\Notification;
use App\Models\StudentModuleAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentModuleAssignmentController extends Controller
{
    public function index(ClassModel $class): View
    {
        $this->authorize('view', $class);

        $moduleAssignments = StudentModuleAssignment::where('class_id', $class->id)
            ->with(['student', 'module'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $students = User::whereHas('enrollments', function ($q) use ($class) {
            $q->where('class_id', $class->id)->where('status', 'active');
        })
            ->orderBy('name')
            ->get();

        $modules = Module::where('course_id', $class->course_id)
            ->published()
            ->orderBy('order')
            ->get();

        return view('instructor.student_module_assignments.index', compact(
            'class',
            'moduleAssignments',
            'students',
            'modules'
        ));
    }

    public function create(ClassModel $class): View
    {
        $this->authorize('update', $class);

        $students = User::whereHas('enrollments', function ($q) use ($class) {
            $q->where('class_id', $class->id)->where('status', 'active');
        })
            ->orderBy('name')
            ->get();

        $modules = Module::where('course_id', $class->course_id)
            ->published()
            ->orderBy('order')
            ->get();

        return view('instructor.student_module_assignments.create', compact(
            'class',
            'students',
            'modules'
        ));
    }

    public function store(Request $request, ClassModel $class)
    {
        $this->authorize('update', $class);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'module_id' => 'required|exists:modules,id',
        ]);

        // Check if assignment already exists
        $existing = StudentModuleAssignment::where('class_id', $class->id)
            ->where('student_id', $validated['student_id'])
            ->where('module_id', $validated['module_id'])
            ->first();

        if ($existing) {
            return redirect()->route('instructor.classes.student_module_assignments.index', $class)
                ->with('error', 'This module is already assigned to this student.');
        }

        $assignment = StudentModuleAssignment::create([
            'class_id' => $class->id,
            'student_id' => $validated['student_id'],
            'module_id' => $validated['module_id'],
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        // Create notification for the student
        $module = Module::find($validated['module_id']);
        $student = User::find($validated['student_id']);

        Notification::create([
            'user_id' => $student->id,
            'type' => 'module_assigned',
            'title' => 'New Module Assigned',
            'body' => "You have been assigned to module: {$module->title} in course: {$class->course->title}",
            'notifiable_type' => StudentModuleAssignment::class,
            'notifiable_id' => $assignment->id,
            'data' => [
                'module_id' => $module->id,
                'class_id' => $class->id,
                'course_id' => $class->course_id,
            ],
            'link_url' => route('student.courses.modules.show', [$class->course, $module]),
            'channel' => 'in_app',
            'status' => 'unread',
            'is_read' => false,
        ]);

        return redirect()->route('instructor.classes.student_module_assignments.index', $class)
            ->with('success', 'Module assigned to student successfully.');
    }

    public function destroy(ClassModel $class, StudentModuleAssignment $assignment)
    {
        $this->authorize('update', $class);

        if ($assignment->class_id !== $class->id) {
            abort(403);
        }

        $assignment->delete();

        return redirect()->route('instructor.classes.student_module_assignments.index', $class)
            ->with('success', 'Module assignment removed successfully.');
    }
}
