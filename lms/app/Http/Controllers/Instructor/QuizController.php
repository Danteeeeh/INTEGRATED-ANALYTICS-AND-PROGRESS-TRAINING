<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Course $course): View
    {
        $this->authorize('viewAny', Quiz::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quizzes = Quiz::whereHas('class.course', fn ($q) => $q->where('id', $course->id))
            ->orWhere(function ($q) use ($course) {
                $q->whereHas('module.course', fn ($q2) => $q2->where('id', $course->id));
            })
            ->orWhere(function ($q) use ($course) {
                $q->whereHas('lesson.module.course', fn ($q2) => $q2->where('id', $course->id));
            })
            ->with(['class', 'module', 'lesson', 'questions', 'attempts'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('instructor.courses.quizzes.index', compact('course', 'quizzes'));
    }

    public function create(Course $course): View
    {
        $this->authorize('create', Quiz::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();
        $modules = Module::where('course_id', $course->id)->with('lessons')->get();
        $resultVisibilityOptions = [
            Quiz::VISIBILITY_ALWAYS => 'Always Visible',
            Quiz::VISIBILITY_AFTER_GRADING => 'After Grading',
            Quiz::VISIBILITY_NEVER => 'Never Visible',
        ];

        return view('instructor.courses.quizzes.create', compact('course', 'classes', 'modules', 'resultVisibilityOptions'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'attempt_limit' => 'nullable|integer|min:1',
            'passing_score_percent' => 'nullable|integer|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_choices' => 'boolean',
            'allow_navigation' => 'boolean',
            'auto_save_seconds' => 'nullable|integer|min:30',
            'auto_submit_on_timeout' => 'boolean',
            'result_visibility' => 'required|string|in:always,after_grading,never',
            'review_allowed' => 'boolean',
            'show_correct_answers' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'status' => 'required|string|in:draft,published,closed',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['created_by'] = auth()->id();
        $validated['shuffle_questions'] = $validated['shuffle_questions'] ?? false;
        $validated['shuffle_choices'] = $validated['shuffle_choices'] ?? false;
        $validated['allow_navigation'] = $validated['allow_navigation'] ?? true;
        $validated['auto_submit_on_timeout'] = $validated['auto_submit_on_timeout'] ?? false;
        $validated['review_allowed'] = $validated['review_allowed'] ?? true;
        $validated['show_correct_answers'] = $validated['show_correct_answers'] ?? false;

        Quiz::create($validated);

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', 'Quiz created successfully.');
    }

    public function show(Course $course, Quiz $quiz): View
    {
        $this->authorize('view', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->load(['class', 'module', 'lesson', 'questions.choices', 'attempts.student']);

        return view('instructor.courses.quizzes.show', compact('course', 'quiz'));
    }

    public function edit(Course $course, Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $classes = ClassModel::where('course_id', $course->id)
            ->where('instructor_id', auth()->id())
            ->get();
        $modules = Module::where('course_id', $course->id)->with('lessons')->get();
        $resultVisibilityOptions = [
            Quiz::VISIBILITY_ALWAYS => 'Always Visible',
            Quiz::VISIBILITY_AFTER_GRADING => 'After Grading',
            Quiz::VISIBILITY_NEVER => 'Never Visible',
        ];

        return view('instructor.courses.quizzes.edit', compact('course', 'quiz', 'classes', 'modules', 'resultVisibilityOptions'));
    }

    public function update(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'attempt_limit' => 'nullable|integer|min:1',
            'passing_score_percent' => 'nullable|integer|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_choices' => 'boolean',
            'allow_navigation' => 'boolean',
            'auto_save_seconds' => 'nullable|integer|min:30',
            'auto_submit_on_timeout' => 'boolean',
            'result_visibility' => 'required|string|in:always,after_grading,never',
            'review_allowed' => 'boolean',
            'show_correct_answers' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'status' => 'required|string|in:draft,published,closed',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['shuffle_questions'] = $validated['shuffle_questions'] ?? false;
        $validated['shuffle_choices'] = $validated['shuffle_choices'] ?? false;
        $validated['allow_navigation'] = $validated['allow_navigation'] ?? true;
        $validated['auto_submit_on_timeout'] = $validated['auto_submit_on_timeout'] ?? false;
        $validated['review_allowed'] = $validated['review_allowed'] ?? true;
        $validated['show_correct_answers'] = $validated['show_correct_answers'] ?? false;

        $quiz->update($validated);

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->delete();

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', 'Quiz deleted successfully.');
    }

    public function publish(Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->update(['status' => Quiz::STATUS_PUBLISHED]);

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', 'Quiz published successfully.');
    }

    public function close(Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->update(['status' => Quiz::STATUS_CLOSED]);

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', 'Quiz closed successfully.');
    }

    public function attempts(Course $course, Quiz $quiz): View
    {
        $this->authorize('view', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $quiz->load('attempts.student');
        $attempts = $quiz->attempts()->with('student')->orderBy('created_at', 'desc')->paginate(20);

        return view('instructor.courses.quizzes.attempts', compact('course', 'quiz', 'attempts'));
    }

    public function showAttempt(Course $course, Quiz $quiz, QuizAttempt $attempt): View
    {
        $this->authorize('view', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($attempt->quiz_id !== $quiz->id, 404);

        $attempt->load('student', 'answers.question.choices', 'answers.choice');

        return view('instructor.courses.quizzes.attempt', compact('course', 'quiz', 'attempt'));
    }

    public function gradeAttempt(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);
        abort_if($attempt->quiz_id !== $quiz->id, 404);

        $validated = $request->validate([
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $attempt->update([
            'score' => $validated['score'],
            'score_percent' => isset($quiz->passing_score_percent) ? ($validated['score'] / 100) * 100 : null,
            'is_passed' => isset($quiz->passing_score_percent) && $attempt->score_percent >= $quiz->passing_score_percent,
            'status' => QuizAttempt::STATUS_GRADED,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        return redirect()->route('instructor.courses.quizzes.attempts.show', [$course, $quiz, $attempt])
            ->with('success', 'Attempt graded successfully.');
    }
}
