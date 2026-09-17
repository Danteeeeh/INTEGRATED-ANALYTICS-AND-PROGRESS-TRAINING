<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MediaFile;
use App\Models\SubmissionFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
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

        $assignments = Assignment::published()
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->with(['submissions' => function ($q) use ($studentId) {
                $q->ofStudent($studentId);
            }])
            ->orderBy('due_date', 'asc')
            ->paginate(10);

        return view('student.assignments.index', compact('course', 'assignments', 'enrollment'));
    }

    public function show(Course $course, Assignment $assignment): View
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

        $assignment->load('attachments');

        $mySubmission = AssignmentSubmission::ofAssignment($assignment->id)
            ->ofStudent($studentId)
            ->latest()
            ->first();

        return view('student.assignments.show', compact('course', 'assignment', 'enrollment', 'mySubmission'));
    }

    public function submitForm(Course $course, Assignment $assignment): View
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

        $existingSubmission = AssignmentSubmission::ofAssignment($assignment->id)
            ->ofStudent($studentId)
            ->latest()
            ->first();

        if ($existingSubmission && ! $assignment->allow_resubmission) {
            return redirect()->route('student.courses.assignments.submissions.show', [$course, $assignment, $existingSubmission])
                ->with('error', 'You have already submitted this assignment and resubmission is not allowed.');
        }

        return view('student.assignments.submit', compact('course', 'assignment', 'enrollment', 'existingSubmission'));
    }

    public function submit(Request $request, Course $course, Assignment $assignment): RedirectResponse
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

        $latestSubmission = AssignmentSubmission::ofAssignment($assignment->id)
            ->ofStudent($studentId)
            ->latest()
            ->first();

        if ($latestSubmission && ! $assignment->allow_resubmission) {
            return back()->with('error', 'You have already submitted this assignment.');
        }

        if ($assignment->max_attempts && $latestSubmission) {
            $attemptCount = AssignmentSubmission::ofAssignment($assignment->id)
                ->ofStudent($studentId)
                ->count();
            if ($attemptCount >= $assignment->max_attempts) {
                return back()->with('error', 'You have reached the maximum number of attempts.');
            }
        }

        $isLate = $assignment->due_date && now()->gt($assignment->due_date);
        if ($isLate && ! $assignment->allow_late) {
            return back()->with('error', 'Late submissions are not allowed for this assignment.');
        }

        $attemptNumber = $latestSubmission ? $latestSubmission->attempt_number + 1 : 1;

        $validated = $request->validate([
            'submission_text' => $assignment->submission_type === Assignment::TYPE_TEXT ? 'required|string' : 'nullable|string',
            'files' => $assignment->submission_type === Assignment::TYPE_FILE || $assignment->submission_type === Assignment::TYPE_MULTIPLE_FILES
                ? 'required'
                : 'nullable',
            'files.*' => $assignment->submission_type === Assignment::TYPE_MULTIPLE_FILES ? 'file|max:10240' : 'file|max:10240',
        ]);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $studentId,
            'attempt_number' => $attemptNumber,
            'submission_text' => $validated['submission_text'] ?? null,
            'submitted_at' => now(),
            'is_late' => $isLate,
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
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

        if ($submission->student_id !== $studentId) {
            abort(403);
        }

        $submission->load('files.mediaFile');

        return view('student.assignments.submission', compact('course', 'assignment', 'submission', 'enrollment'));
    }
}
