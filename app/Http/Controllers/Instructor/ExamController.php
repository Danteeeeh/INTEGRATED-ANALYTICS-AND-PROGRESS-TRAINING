<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(
        protected ExamService $examService
    ) {}

    public function index(Request $request): View
    {
        $instructorId = auth()->id();

        $query = Exam::with(['class.course', 'creator'])
            ->whereHas('class', function ($q) use ($instructorId) {
                $q->where('instructor_id', $instructorId);
            });

        if ($request->filled('course_id')) {
            $query->whereHas('class', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('exam_type')) {
            $query->where('exam_type', $request->exam_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $exams = $query->orderByDesc('created_at')->paginate(15);
        $courses = Course::whereHas('classes', function ($q) use ($instructorId) {
            $q->where('instructor_id', $instructorId);
        })->orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::where('instructor_id', $instructorId)
            ->with('course')
            ->orderBy('code')
            ->get(['id', 'code', 'course_id']);

        return view('instructor.exams.index', compact('exams', 'courses', 'classes'));
    }

    public function create(): View
    {
        $instructorId = auth()->id();

        $courses = Course::whereHas('classes', function ($q) use ($instructorId) {
            $q->where('instructor_id', $instructorId);
        })->orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::where('instructor_id', $instructorId)
            ->with('course')
            ->orderBy('code')
            ->get(['id', 'code', 'course_id']);

        return view('instructor.exams.create', compact('courses', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'nullable|exists:courses,id',
            'module_id' => 'nullable|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'exam_type' => 'required|in:midterm,final,comprehensive,module,other',
            'duration_minutes' => 'required|integer|min:1',
            'attempt_limit' => 'nullable|integer|min:1',
            'passing_score_percent' => 'nullable|integer|min:0|max:100',
            'grade_weight' => 'nullable|integer|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_choices' => 'boolean',
            'allow_navigation' => 'boolean',
            'auto_save_seconds' => 'nullable|integer|min:0',
            'auto_submit_on_timeout' => 'boolean',
            'result_visibility' => 'required|in:immediately,after_grading,after_all_submissions,after_date,never',
            'allow_review' => 'boolean',
            'show_correct_answers' => 'boolean',
            'show_score' => 'boolean',
            'results_release_date' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'allowed_start_time' => 'nullable|date_format:H:i',
            'allowed_end_time' => 'nullable|date_format:H:i|after:allowed_start_time',
            'video_url' => 'nullable|url|max:500',
            'video_duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|in:draft,published,closed',
            // Proctoring
            'requires_proctoring' => 'boolean',
            'proctoring_method' => 'nullable|in:in_person,online,ai_proctor,hybrid',
            'proctoring_instructions' => 'nullable|string',
            'record_session' => 'boolean',
            'detect_tab_switch' => 'boolean',
            'detect_copy_paste' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['shuffle_questions'] = $request->boolean('shuffle_questions', true);
        $validated['shuffle_choices'] = $request->boolean('shuffle_choices', true);
        $validated['allow_navigation'] = $request->boolean('allow_navigation', false);
        $validated['auto_submit_on_timeout'] = $request->boolean('auto_submit_on_timeout', true);
        $validated['allow_review'] = $request->boolean('allow_review', false);
        $validated['show_correct_answers'] = $request->boolean('show_correct_answers', false);
        $validated['show_score'] = $request->boolean('show_score', false);
        $validated['requires_proctoring'] = $request->boolean('requires_proctoring', false);
        $validated['record_session'] = $request->boolean('record_session', false);
        $validated['detect_tab_switch'] = $request->boolean('detect_tab_switch', false);
        $validated['detect_copy_paste'] = $request->boolean('detect_copy_paste', false);

        $exam = $this->examService->createExam($validated);

        session()->flash('success', 'Exam created successfully.');

        return redirect()->route('instructor.courses.exams.show', [$exam->course, $exam]);
    }

    public function show(Course $course, Exam $exam): View
    {
        // Verify instructor owns this exam
        if ($exam->class->instructor_id !== auth()->id()) {
            abort(403);
        }

        $exam->load(['class.course', 'module', 'creator', 'questions.choices', 'attempts.student']);

        return view('instructor.exams.show', compact('course', 'exam'));
    }

    public function edit(Course $course, Exam $exam): View
    {
        // Verify instructor owns this exam
        if ($exam->class->instructor_id !== auth()->id()) {
            abort(403);
        }

        $instructorId = auth()->id();

        $courses = Course::whereHas('classes', function ($q) use ($instructorId) {
            $q->where('instructor_id', $instructorId);
        })->orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::where('instructor_id', $instructorId)
            ->with('course')
            ->orderBy('code')
            ->get(['id', 'code', 'course_id']);

        return view('instructor.exams.edit', compact('course', 'exam', 'courses', 'classes'));
    }

    public function update(Request $request, Course $course, Exam $exam): RedirectResponse
    {
        // Verify instructor owns this exam
        if ($exam->class->instructor_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'nullable|exists:courses,id',
            'module_id' => 'nullable|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'exam_type' => 'required|in:midterm,final,comprehensive,module,other',
            'duration_minutes' => 'required|integer|min:1',
            'attempt_limit' => 'nullable|integer|min:1',
            'passing_score_percent' => 'nullable|integer|min:0|max:100',
            'grade_weight' => 'nullable|integer|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_choices' => 'boolean',
            'allow_navigation' => 'boolean',
            'auto_save_seconds' => 'nullable|integer|min:0',
            'auto_submit_on_timeout' => 'boolean',
            'result_visibility' => 'required|in:immediately,after_grading,after_all_submissions,after_date,never',
            'allow_review' => 'boolean',
            'show_correct_answers' => 'boolean',
            'show_score' => 'boolean',
            'results_release_date' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'allowed_start_time' => 'nullable|date_format:H:i',
            'allowed_end_time' => 'nullable|date_format:H:i|after:allowed_start_time',
            'video_url' => 'nullable|url|max:500',
            'video_duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|in:draft,published,closed',
            // Proctoring
            'requires_proctoring' => 'boolean',
            'proctoring_method' => 'nullable|in:in_person,online,ai_proctor,hybrid',
            'proctoring_instructions' => 'nullable|string',
            'record_session' => 'boolean',
            'detect_tab_switch' => 'boolean',
            'detect_copy_paste' => 'boolean',
        ]);

        $validated['shuffle_questions'] = $request->boolean('shuffle_questions', $exam->shuffle_questions);
        $validated['shuffle_choices'] = $request->boolean('shuffle_choices', $exam->shuffle_choices);
        $validated['allow_navigation'] = $request->boolean('allow_navigation', $exam->allow_navigation);
        $validated['auto_submit_on_timeout'] = $request->boolean('auto_submit_on_timeout', $exam->auto_submit_on_timeout);
        $validated['allow_review'] = $request->boolean('allow_review', $exam->allow_review);
        $validated['show_correct_answers'] = $request->boolean('show_correct_answers', $exam->show_correct_answers);
        $validated['show_score'] = $request->boolean('show_score', $exam->show_score);
        $validated['requires_proctoring'] = $request->boolean('requires_proctoring', $exam->requires_proctoring);
        $validated['record_session'] = $request->boolean('record_session', $exam->record_session);
        $validated['detect_tab_switch'] = $request->boolean('detect_tab_switch', $exam->detect_tab_switch);
        $validated['detect_copy_paste'] = $request->boolean('detect_copy_paste', $exam->detect_copy_paste);

        $exam = $this->examService->updateExam($exam, $validated);

        session()->flash('success', 'Exam updated successfully.');

        return redirect()->route('instructor.courses.exams.show', [$course, $exam]);
    }

    public function destroy(Course $course, Exam $exam): RedirectResponse
    {
        // Verify instructor owns this exam
        if ($exam->class->instructor_id !== auth()->id()) {
            abort(403);
        }

        try {
            if ($exam->attempts()->exists()) {
                return back()->with('error', 'Cannot delete an exam that has attempts.');
            }
            $this->examService->deleteExam($exam);
            session()->flash('success', 'Exam archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('instructor.courses.exams.index', $course);
    }

    public function publish(Course $course, Exam $exam): RedirectResponse
    {
        // Verify instructor owns this exam
        if ($exam->class->instructor_id !== auth()->id()) {
            abort(403);
        }

        $exam = $this->examService->publishExam($exam);

        session()->flash('success', 'Exam published successfully.');

        return back();
    }

    public function close(Course $course, Exam $exam): RedirectResponse
    {
        // Verify instructor owns this exam
        if ($exam->class->instructor_id !== auth()->id()) {
            abort(403);
        }

        $exam = $this->examService->closeExam($exam);

        session()->flash('success', 'Exam closed successfully.');

        return back();
    }

    public function grantExtension(Request $request, Course $course, Exam $exam): RedirectResponse
    {
        $this->authorize('extendDeadline', $exam);

        // Verify instructor owns this exam
        if ($exam->class->instructor_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'extended_until' => 'required|date|after:now',
            'reason' => 'nullable|string',
        ]);

        $studentId = $validated['student_id'];

        // Check if student is enrolled in the course
        $enrollment = \App\Models\Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'Student is not enrolled in this course.');
        }

        // Update or create extension
        \App\Models\ExamExtension::updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $studentId,
            ],
            [
                'extended_until' => $validated['extended_until'],
                'reason' => $validated['reason'] ?? null,
                'granted_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Exam extension granted successfully.');
    }

    public function revokeExtension(Course $course, Exam $exam, int $studentId): RedirectResponse
    {
        $this->authorize('extendDeadline', $exam);

        // Verify instructor owns this exam
        if ($exam->class->instructor_id !== auth()->id()) {
            abort(403);
        }

        $extension = \App\Models\ExamExtension::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->first();

        if ($extension) {
            $extension->delete();
        }

        return back()->with('success', 'Exam extension revoked successfully.');
    }
}
