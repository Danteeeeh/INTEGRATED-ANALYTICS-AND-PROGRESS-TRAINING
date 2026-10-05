<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Module;
use App\Models\Rubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Course $course): View
    {
        $this->authorize('viewAny', Assignment::class);

        $instructorId = auth()->id();

        $assignments = Assignment::query()
            ->where(function ($query) use ($course) {
                $query->whereHas('class.course', fn ($q) => $q->where('id', $course->id))
                    ->orWhereHas('module.course', fn ($q) => $q->where('id', $course->id))
                    ->orWhereHas('lesson.module.course', fn ($q) => $q->where('id', $course->id));
            })
            ->where(function ($query) use ($instructorId, $course) {
                // If assignment is linked to a class, check if instructor teaches that class
                $query->whereHas('class', fn ($q) => $q->where('instructor_id', $instructorId))
                    // If assignment is linked to module/lesson, check if instructor teaches ANY class in the course
                    ->orWhereHas('module.course.classes', fn ($q) => $q->where('instructor_id', $instructorId))
                    ->orWhereHas('lesson.module.course.classes', fn ($q) => $q->where('instructor_id', $instructorId))
                    // Or if the instructor created the assignment
                    ->orWhere('created_by', $instructorId);
            })
            ->with(['class', 'module', 'rubric', 'submissions'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('instructor.courses.assignments.index', compact('course', 'assignments'));
    }

    public function create(Course $course): View
    {
        $this->authorize('create', Assignment::class);

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

        $isDraft = $request->input('status') === 'draft';

        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'instructions' => $isDraft ? 'nullable|string' : 'required|string',
            'points' => $isDraft ? 'nullable|integer|min:0' : 'required|integer|min:0',
            'submission_type' => $isDraft ? 'nullable|string|in:text,file,multiple_files' : 'required|string|in:text,file,multiple_files',
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
        $validated['slug'] = Str::slug($validated['title']).'-'.Str::lower(Str::random(8));
        $validated['allow_late'] = isset($validated['allow_late']);
        $validated['allow_resubmission'] = isset($validated['allow_resubmission']);

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

        $isDraft = $request->input('status') === 'draft';

        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'instructions' => $isDraft ? 'nullable|string' : 'required|string',
            'points' => $isDraft ? 'nullable|integer|min:0' : 'required|integer|min:0',
            'submission_type' => $isDraft ? 'nullable|string|in:text,file,multiple_files' : 'required|string|in:text,file,multiple_files',
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

        $validated['allow_late'] = isset($validated['allow_late']);
        $validated['allow_resubmission'] = isset($validated['allow_resubmission']);

        $assignment->update($validated);

        return redirect()->route('instructor.courses.assignments.index', $course)
            ->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Course $course, Assignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        $assignment->delete();

        return redirect()->route('instructor.courses.assignments.index', $course)
            ->with('success', 'Assignment deleted successfully.');
    }

    public function publish(Course $course, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $assignment->update(['status' => Assignment::STATUS_PUBLISHED]);

        return redirect()->route('instructor.courses.assignments.index', $course)
            ->with('success', 'Assignment published successfully.');
    }

    public function close(Course $course, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $assignment->update(['status' => Assignment::STATUS_CLOSED]);

        return redirect()->route('instructor.courses.assignments.index', $course)
            ->with('success', 'Assignment closed successfully.');
    }

    public function submissions(Course $course, Assignment $assignment): View
    {
        $this->authorize('view', $assignment);

        $assignment->load('submissions.student');
        $submissions = $assignment->submissions()->with('student', 'files')->paginate(20);

        return view('instructor.courses.assignments.submissions', compact('course', 'assignment', 'submissions'));
    }

    public function showSubmission(Course $course, Assignment $assignment, AssignmentSubmission $submission): View
    {
        $this->authorize('view', $assignment);
        abort_if($submission->assignment_id !== $assignment->id, 404);
        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $submission->load('student', 'files', 'rubricAssessments.criterion');

        return view('instructor.courses.assignments.submission', compact('course', 'assignment', 'submission'));
    }

    public function gradeSubmission(Request $request, Course $course, Assignment $assignment, AssignmentSubmission $submission): RedirectResponse
    {
        $this->authorize('grade', $submission);
        abort_if($submission->assignment_id !== $assignment->id, 404);
        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'points' => 'required|numeric|min:0|max:'.$assignment->points,
            'feedback' => 'nullable|string',
        ]);

        $scorePercent = ($validated['points'] / $assignment->points) * 100;

        $classId = $assignment->class_id;
        if (! $classId) {
            $courseId = $assignment->resolveCourseId();
            if ($courseId) {
                $enrollment = Enrollment::where('student_id', $submission->student_id)
                    ->whereHas('class', function ($q) use ($courseId) {
                        $q->where('course_id', $courseId);
                    })
                    ->where('status', 'active')
                    ->first();
                $classId = $enrollment?->class_id;
            }
        }

        // If still no class_id, we can still grade but won't link to a specific class gradebook
        if (! $classId) {
            $submission->update([
                'status' => AssignmentSubmission::STATUS_GRADED,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);

            return redirect()->route('instructor.courses.assignments.submissions.show', [$course, $assignment, $submission])
                ->with('success', 'Submission graded successfully (not linked to gradebook - assignment has no class).');
        }

        $submission->update([
            'status' => AssignmentSubmission::STATUS_GRADED,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        $grade = Grade::where('student_id', $submission->student_id)
            ->whereHas('item', function ($query) use ($assignment, $classId) {
                $query->where('related_type', Assignment::class)
                    ->where('related_id', $assignment->id)
                    ->where('class_id', $classId);
            })
            ->first();

        if ($grade) {
            $grade->update([
                'points' => $validated['points'],
                'score_percent' => $scorePercent,
                'feedback' => $validated['feedback'] ?? null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);
        } else {
            $gradeItem = GradeItem::firstOrCreate([
                'related_type' => Assignment::class,
                'related_id' => $assignment->id,
                'class_id' => $classId,
            ], [
                'title' => $assignment->title,
                'max_points' => $assignment->points,
                'item_type' => GradeItem::TYPE_ASSIGNMENT,
                'is_released' => true,
                'released_at' => now(),
            ]);

            Grade::create([
                'grade_item_id' => $gradeItem->id,
                'student_id' => $submission->student_id,
                'points' => $validated['points'],
                'score_percent' => $scorePercent,
                'feedback' => $validated['feedback'] ?? null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);
        }

        return redirect()->route('instructor.courses.assignments.submissions.show', [$course, $assignment, $submission])
            ->with('success', 'Submission graded successfully.');
    }

    public function grantExtension(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'extended_until' => 'required|date|after:now',
            'reason' => 'nullable|string',
        ]);

        $studentId = $validated['student_id'];

        // Check if student is enrolled in the course
        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'Student is not enrolled in this course.');
        }

        // Update or create extension
        \App\Models\AssignmentExtension::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $studentId,
            ],
            [
                'extended_until' => $validated['extended_until'],
                'reason' => $validated['reason'] ?? null,
                'granted_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Assignment extension granted successfully.');
    }

    public function revokeExtension(Course $course, Assignment $assignment, int $studentId): RedirectResponse
    {
        $this->authorize('update', $assignment);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $extension = \App\Models\AssignmentExtension::where('assignment_id', $assignment->id)
            ->where('student_id', $studentId)
            ->first();

        if ($extension) {
            $extension->delete();
        }

        return back()->with('success', 'Assignment extension revoked successfully.');
    }
}
