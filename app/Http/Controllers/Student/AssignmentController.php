<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MediaFile;
use App\Models\SubmissionFile;
use App\Services\ContentProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function __construct(private ContentProgressService $contentProgress) {}

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

        // The same section scope the dashboard and course overview use: another
        // block's class of this course must not stock this list, and module
        // work has to sit in one of this section's assigned modules.
        $classIds = $this->contentProgress->sectionScopedClassIdsForCourse($studentId, $course->id);

        $assignments = $this->contentProgress
            ->sectionScopedAssignmentsQuery($classIds, collect([$course->id]))
            ->with(['submissions' => function ($q) use ($studentId) {
                $q->ofStudent($studentId);
            }])
            ->orderBy('due_date', 'asc')
            ->paginate(10);

        return view('student.assignments.index', compact('course', 'assignments', 'enrollment'));
    }

    public function show(Course $course, Assignment $assignment): View
    {
        $this->authorize('view', $assignment);

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

        $assignment->load('attachments');

        $mySubmission = AssignmentSubmission::ofAssignment($assignment->id)
            ->ofStudent($studentId)
            ->latest()
            ->first();

        $isOverdue = $assignment->isOverdueForStudent($studentId);
        $effectiveDeadline = $assignment->getEffectiveDeadlineForStudent($studentId);

        return view('student.assignments.show', compact('course', 'assignment', 'enrollment', 'mySubmission', 'isOverdue', 'effectiveDeadline'));
    }

    public function submitForm(Course $course, Assignment $assignment): View|RedirectResponse
    {
        $this->authorize('submit', $assignment);

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

        $existingSubmission = AssignmentSubmission::ofAssignment($assignment->id)
            ->ofStudent($studentId)
            ->latest()
            ->first();

        if ($existingSubmission && ! $assignment->allow_resubmission) {
            return redirect()->route('student.courses.assignments.submissions.show', [$course, $assignment, $existingSubmission])
                ->with('error', 'You have already submitted this assignment and resubmission is not allowed.');
        }

        // The deadline is final: once it passes the form closes. A granted
        // extension is the only way back in, and isOverdueForStudent() already
        // honours it.
        if ($assignment->isOverdueForStudent($studentId)) {
            return redirect()->route('student.courses.assignments.show', [$course, $assignment])
                ->with('error', 'This assignment is overdue and no longer available for submission.');
        }

        $effectiveDeadline = $assignment->getEffectiveDeadlineForStudent($studentId);

        return view('student.assignments.submit', compact('course', 'assignment', 'enrollment', 'existingSubmission', 'effectiveDeadline'));
    }

    public function submit(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        $this->authorize('submit', $assignment);

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

        $latestSubmission = AssignmentSubmission::ofAssignment($assignment->id)
            ->ofStudent($studentId)
            ->latest()
            ->first();

        if ($latestSubmission && ! $assignment->allow_resubmission) {
            return back()->with('error', 'You have already submitted this assignment.');
        }

        // The deadline is final; only a per-student extension reopens it.
        if ($assignment->isOverdueForStudent($studentId)) {
            return back()->with('error', 'This assignment is overdue and no longer available for submission.');
        }

        // `max_attempts` defaults to 1 in the schema, so it only acts as a cap
        // once the instructor raises it above that default. Otherwise it would
        // contradict "allow resubmission" and refuse every resubmission.
        if ($latestSubmission && $assignment->max_attempts > 1) {
            $attemptCount = AssignmentSubmission::ofAssignment($assignment->id)
                ->ofStudent($studentId)
                ->count();
            if ($attemptCount >= $assignment->max_attempts) {
                return back()->with('error', 'You have reached the maximum number of attempts.');
            }
        }

        $attemptNumber = $latestSubmission ? $latestSubmission->attempt_number + 1 : 1;

        // Build validation rules based on submission type
        $rules = [
            'submission_text' => $assignment->submission_type === Assignment::TYPE_TEXT ? 'required|string' : 'nullable|string',
        ];

        if ($assignment->submission_type === Assignment::TYPE_FILE) {
            $rules['files'] = 'required|array';
            $rules['files.*'] = 'required|file|max:'.config('lms.uploads.assignment_submission');
        } elseif ($assignment->submission_type === Assignment::TYPE_MULTIPLE_FILES) {
            $rules['files'] = 'required|array|min:1';
            $rules['files.*'] = 'required|file|max:'.config('lms.uploads.assignment_submission');
        }

        $validated = $request->validate($rules);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $studentId,
            'attempt_number' => $attemptNumber,
            'submission_text' => $validated['submission_text'] ?? null,
            'submitted_at' => now(),
            'is_late' => false,
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ]);

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $path = $file->storeAs(
                    "assignments/{$assignment->id}/submissions/{$submission->id}",
                    uniqid().'_'.$originalName,
                    'local'
                );

                $mediaFile = MediaFile::create([
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $originalName,
                    'file_name' => basename($path),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'extension' => $file->getClientOriginalExtension(),
                    'uploader_id' => $studentId,
                ]);

                SubmissionFile::create([
                    'assignment_submission_id' => $submission->id,
                    'media_file_id' => $mediaFile->id,
                    'original_name' => $originalName,
                ]);
            }
        }

        return redirect()->route('student.courses.assignments.submissions.show', [$course, $assignment, $submission])
            ->with('status', 'Assignment submitted successfully!');
    }

    public function showSubmission(Course $course, Assignment $assignment, AssignmentSubmission $submission): View
    {
        $this->authorize('view', $submission);

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

        $submission->load('files.mediaFile');

        return view('student.assignments.submission', compact('course', 'assignment', 'submission', 'enrollment'));
    }
}
