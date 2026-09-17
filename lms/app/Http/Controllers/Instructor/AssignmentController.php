<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Module;
use App\Models\Rubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Course $course): View
    {
        $this->authorize('viewAny', Assignment::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $assignments = Assignment::whereHas('class.course', fn ($q) => $q->where('id', $course->id))
            ->orWhere(function ($q) use ($course) {
                $q->whereHas('module.course', fn ($q2) => $q2->where('id', $course->id));
            })
            ->orWhere(function ($q) use ($course) {
                $q->whereHas('lesson.module.course', fn ($q2) => $q2->where('id', $course->id));
            })
            ->with(['course', 'class', 'module', 'lesson', 'rubric', 'submissions'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('instructor.courses.assignments.index', compact('course', 'assignments'));
    }

    public function create(Course $course): View
    {
        $this->authorize('create', Assignment::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();
        $modules = Module::where('course_id', $course->id)->with('lessons')->get();
        $rubrics = Rubric::where('created_by', auth()->id())
            ->orWhere('course_id', $course->id)
            ->active()
            ->get();
        $submissionTypes = [
            Assignment::TYPE_TEXT => 'Text',
            Assignment::TYPE_FILE => 'File Upload',
            Assignment::TYPE_MULTIPLE_FILES => 'Multiple Files',
        ];

        return view('instructor.courses.assignments.create', compact('course', 'classes', 'modules', 'rubrics', 'submissionTypes'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('create', Assignment::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'points' => 'required|integer|min:0',
            'submission_type' => 'required|string|in:text,file,multiple_files',
            'due_date' => 'nullable|date',
            'allow_late' => 'boolean',
            'late_submission_deduction_percent' => 'nullable|integer|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'allow_resubmission' => 'boolean',
            'resubmission_deadline' => 'nullable|date|after:due_date',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'rubric_id' => 'nullable|exists:rubrics,id',
            'status' => 'required|string|in:draft,published,closed',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['created_by'] = auth()->id();
        $validated['allow_late'] = $validated['allow_late'] ?? false;
        $validated['allow_resubmission'] = $validated['allow_resubmission'] ?? false;

        Assignment::create($validated);

        return redirect()->route('instructor.courses.assignments.index', $course)
            ->with('success', 'Assignment created successfully.');
    }

    public function show(Course $course, Assignment $assignment): View
    {
        $this->authorize('view', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $assignment->load(['class', 'module', 'lesson', 'rubric.criteria', 'attachments', 'submissions.student']);

        return view('instructor.courses.assignments.show', compact('course', 'assignment'));
    }

    public function edit(Course $course, Assignment $assignment): View
    {
        $this->authorize('update', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();
        $modules = Module::where('course_id', $course->id)->with('lessons')->get();
        $rubrics = Rubric::where('created_by', auth()->id())
            ->orWhere('course_id', $course->id)
            ->active()
            ->get();
        $submissionTypes = [
            Assignment::TYPE_TEXT => 'Text',
            Assignment::TYPE_FILE => 'File Upload',
            Assignment::TYPE_MULTIPLE_FILES => 'Multiple Files',
        ];

        return view('instructor.courses.assignments.edit', compact('course', 'assignment', 'classes', 'modules', 'rubrics', 'submissionTypes'));
    }

    public function update(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'points' => 'required|integer|min:0',
            'submission_type' => 'required|string|in:text,file,multiple_files',
            'due_date' => 'nullable|date',
            'allow_late' => 'boolean',
            'late_submission_deduction_percent' => 'nullable|integer|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'allow_resubmission' => 'boolean',
            'resubmission_deadline' => 'nullable|date|after:due_date',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'rubric_id' => 'nullable|exists:rubrics,id',
            'status' => 'required|string|in:draft,published,closed',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['allow_late'] = $validated['allow_late'] ?? false;
        $validated['allow_resubmission'] = $validated['allow_resubmission'] ?? false;

        $assignment->update($validated);

        return redirect()->route('instructor.courses.assignments.index', $course)
            ->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Course $course, Assignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $assignment->delete();

        return redirect()->route('instructor.courses.assignments.index', $course)
            ->with('success', 'Assignment deleted successfully.');
    }

    public function publish(Course $course, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $assignment->update(['status' => Assignment::STATUS_PUBLISHED]);

        return redirect()->route('instructor.courses.assignments.index', $course)
            ->with('success', 'Assignment published successfully.');
    }

    public function close(Course $course, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $assignment->update(['status' => Assignment::STATUS_CLOSED]);

        return redirect()->route('instructor.courses.assignments.index', $course)
            ->with('success', 'Assignment closed successfully.');
    }

    public function submissions(Course $course, Assignment $assignment): View
    {
        $this->authorize('view', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $assignment->load('submissions.student');
        $submissions = $assignment->submissions()->with('student', 'files')->paginate(20);

        return view('instructor.courses.assignments.submissions', compact('course', 'assignment', 'submissions'));
    }

    public function showSubmission(Course $course, Assignment $assignment, AssignmentSubmission $submission): View
    {
        $this->authorize('view', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($submission->assignment_id !== $assignment->id, 404);

        $submission->load('student', 'files', 'rubricAssessments.criterion', 'grade');

        return view('instructor.courses.assignments.submission', compact('course', 'assignment', 'submission'));
    }

    public function gradeSubmission(Request $request, Course $course, Assignment $assignment, AssignmentSubmission $submission): RedirectResponse
    {
        $this->authorize('update', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($submission->assignment_id !== $assignment->id, 404);

        $validated = $request->validate([
            'points' => 'required|numeric|min:0|max:'.$assignment->points,
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'status' => AssignmentSubmission::STATUS_GRADED,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        $scorePercent = ($validated['points'] / $assignment->points) * 100;

        $submission->grade()->updateOrCreate(
            ['gradable_type' => AssignmentSubmission::class],
            [
                'grade_item_id' => null,
                'student_id' => $submission->student_id,
                'points' => $validated['points'],
                'score_percent' => $scorePercent,
                'feedback' => $validated['feedback'] ?? null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]
        );

        return redirect()->route('instructor.courses.assignments.submissions.show', [$course, $assignment, $submission])
            ->with('success', 'Submission graded successfully.');
    }
}
