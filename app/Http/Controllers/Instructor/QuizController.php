<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            ->with(['class', 'module', 'questions', 'attempts'])
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

        $isDraft = $request->input('status') === 'draft';

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
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
            'result_visibility' => $isDraft ? 'nullable|string|in:always,after_grading,never' : 'required|string|in:always,after_grading,never',
            'review_allowed' => 'boolean',
            'show_correct_answers' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'status' => 'required|string|in:draft,published,closed',
            'import_file' => 'nullable|file|mimes:csv,txt|max:10240',
            'question_bank_id' => 'nullable|exists:question_banks,id',
            'questions' => 'nullable|array',
            'questions.*.text' => 'required_with:questions|string',
            'questions.*.points' => 'required_with:questions|integer|min:1',
            'questions.*.choices' => 'required_with:questions|array',
            'questions.*.choices.*' => 'required_with:questions|string',
            'questions.*.correct_choice' => 'required_with:questions|integer',
        ]);

        if ($request->filled('class_id')) {
            $class = ClassModel::find($request->class_id);
            abort_if(! $class || $class->instructor_id !== auth()->id(), 403);
        }

        $validated['created_by'] = auth()->id();
        $validated['slug'] = Str::slug($validated['title']).'-'.Str::lower(Str::random(8));
        $validated['shuffle_questions'] = $validated['shuffle_questions'] ?? false;
        $validated['shuffle_choices'] = $validated['shuffle_choices'] ?? false;
        $validated['allow_navigation'] = $validated['allow_navigation'] ?? true;
        $validated['auto_submit_on_timeout'] = $validated['auto_submit_on_timeout'] ?? false;
        $validated['review_allowed'] = $validated['review_allowed'] ?? true;
        $validated['show_correct_answers'] = $validated['show_correct_answers'] ?? false;

        $quiz = Quiz::create($validated);

        // Add manual questions if provided
        if ($request->filled('questions')) {
            $this->addQuestionsToQuiz($quiz, $request->questions, $request->user()->id);
        }

        // Import questions if file is provided
        if ($request->hasFile('import_file')) {
            try {
                $file = $request->file('import_file');
                $filePath = $file->getRealPath();
                $extension = strtolower($file->getClientOriginalExtension());

                $questionBank = $request->filled('question_bank_id')
                    ? \App\Models\QuestionBank::find($request->question_bank_id)
                    : null;

                $importService = new QuizImportService();
                $result = $importService->importQuestionsFromFile(
                    $filePath,
                    $quiz,
                    $request->user()->id,
                    $questionBank,
                    $extension
                );

                $message = "Quiz created successfully. Imported {$result['created']} questions.";
                if (! empty($result['errors'])) {
                    $message .= " Some rows had errors: " . implode('; ', array_slice($result['errors'], 0, 3));
                    if (count($result['errors']) > 3) {
                        $message .= " and " . (count($result['errors']) - 3) . " more.";
                    }
                }

                return redirect()->route('instructor.courses.quizzes.index', $course)
                    ->with('success', $message);
            } catch (\Exception $e) {
                return redirect()->route('instructor.courses.quizzes.index', $course)
                    ->with('success', 'Quiz created successfully, but question import failed: ' . $e->getMessage());
            }
        }

        $questionCount = $quiz->questions()->count();
        $message = $questionCount > 0
            ? "Quiz created successfully with {$questionCount} question(s)."
            : 'Quiz created successfully. Add questions to complete setup.';

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', $message);
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

        $quiz->load(['questions.choices']);

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

        $isDraft = $request->input('status') === 'draft';

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
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
            'result_visibility' => $isDraft ? 'nullable|string|in:always,after_grading,never' : 'required|string|in:always,after_grading,never',
            'review_allowed' => 'boolean',
            'show_correct_answers' => 'boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after:availability_from',
            'status' => 'required|string|in:draft,published,closed',
            'import_file' => 'nullable|file|mimes:csv,txt|max:10240',
            'question_bank_id' => 'nullable|exists:question_banks,id',
            'questions' => 'nullable|array',
            'questions.*.text' => 'required_with:questions|string',
            'questions.*.points' => 'required_with:questions|integer|min:1',
            'questions.*.choices' => 'required_with:questions|array',
            'questions.*.choices.*' => 'required_with:questions|string',
            'questions.*.correct_choice' => 'required_with:questions|integer',
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

        // Update questions if provided
        if ($request->filled('questions')) {
            // Remove existing quiz-question relationships
            $quiz->questions()->detach();

            // Add/update questions
            $this->addQuestionsToQuiz($quiz, $request->questions, $request->user()->id);
        }

        // Import questions if file is provided
        if ($request->hasFile('import_file')) {
            try {
                $file = $request->file('import_file');
                $filePath = $file->getRealPath();
                $extension = strtolower($file->getClientOriginalExtension());

                $questionBank = $request->filled('question_bank_id')
                    ? \App\Models\QuestionBank::find($request->question_bank_id)
                    : null;

                $importService = new QuizImportService();
                $result = $importService->importQuestionsFromFile(
                    $filePath,
                    $quiz,
                    $request->user()->id,
                    $questionBank,
                    $extension
                );

                $message = "Quiz updated successfully. Imported {$result['created']} questions.";
                if (! empty($result['errors'])) {
                    $message .= " Some rows had errors: " . implode('; ', array_slice($result['errors'], 0, 3));
                    if (count($result['errors']) > 3) {
                        $message .= " and " . (count($result['errors']) - 3) . " more.";
                    }
                }

                return redirect()->route('instructor.courses.quizzes.index', $course)
                    ->with('success', $message);
            } catch (\Exception $e) {
                return redirect()->route('instructor.courses.quizzes.index', $course)
                    ->with('success', 'Quiz updated successfully, but question import failed: ' . $e->getMessage());
            }
        }

        $questionCount = $quiz->questions()->count();
        $message = "Quiz updated successfully with {$questionCount} question(s).";

        return redirect()->route('instructor.courses.quizzes.index', $course)
            ->with('success', $message);
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

    public function importQuestions(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $validated = $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:10240',
            'question_bank_id' => 'nullable|exists:question_banks,id',
        ]);

        $file = $request->file('import_file');
        $filePath = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $questionBank = $request->filled('question_bank_id')
                ? \App\Models\QuestionBank::find($request->question_bank_id)
                : null;

            $importService = new QuizImportService();
            $result = $importService->importQuestionsFromFile(
                $filePath,
                $quiz,
                $request->user()->id,
                $questionBank,
                $extension
            );

            $message = "Imported {$result['created']} questions successfully.";
            if (! empty($result['errors'])) {
                $message .= " Some rows had errors: " . implode('; ', array_slice($result['errors'], 0, 3));
                if (count($result['errors']) > 3) {
                    $message .= " and " . (count($result['errors']) - 3) . " more.";
                }
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Add questions to a quiz from form data
     */
    protected function addQuestionsToQuiz(Quiz $quiz, array $questionsData, int $userId): void
    {
        $position = 1;

        foreach ($questionsData as $questionData) {
            $courseId = $quiz->class?->course_id
                ?? $quiz->module?->course_id
                ?? $quiz->lesson?->module?->course_id;

            // Create or find a question bank for this quiz
            $questionBank = \App\Models\QuestionBank::firstOrCreate(
                [
                    'course_id' => $courseId,
                    'title' => $quiz->title . ' Questions',
                    'created_by' => $userId,
                ],
                [
                    'description' => 'Questions for quiz: ' . $quiz->title,
                    'status' => 'active',
                ]
            );

            // Create the question
            $question = \App\Models\Question::create([
                'question_bank_id' => $questionBank->id,
                'question_type' => 'multiple_choice',
                'question_text' => $questionData['text'],
                'default_points' => $questionData['points'] ?? 1,
                'difficulty' => 'medium',
                'created_by' => $userId,
                'status' => 'active',
            ]);

            // Create choices
            $correctChoiceIndex = $questionData['correct_choice'];
            foreach ($questionData['choices'] as $index => $choiceText) {
                \App\Models\QuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_text' => $choiceText,
                    'is_correct' => ($index + 1) == $correctChoiceIndex,
                    'position' => $index + 1,
                ]);
            }

            // Attach question to quiz
            $quiz->questions()->attach($question->id, [
                'position' => $position++,
                'points' => $questionData['points'] ?? 1,
            ]);
        }
    }

    public function grantExtension(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

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
        \App\Models\QuizExtension::updateOrCreate(
            [
                'quiz_id' => $quiz->id,
                'student_id' => $studentId,
            ],
            [
                'extended_until' => $validated['extended_until'],
                'reason' => $validated['reason'] ?? null,
                'granted_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Quiz extension granted successfully.');
    }

    public function revokeExtension(Course $course, Quiz $quiz, int $studentId): RedirectResponse
    {
        $this->authorize('update', $quiz);

        abort_if(! $course->isManagedBy(auth()->user()), 403);

        $extension = \App\Models\QuizExtension::where('quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->first();

        if ($extension) {
            $extension->delete();
        }

        return back()->with('success', 'Quiz extension revoked successfully.');
    }
    {
        $position = 1;

        foreach ($questionsData as $questionData) {
            $courseId = $quiz->class?->course_id
                ?? $quiz->module?->course_id
                ?? $quiz->lesson?->module?->course_id;

            // Create or find a question bank for this quiz
            $questionBank = \App\Models\QuestionBank::firstOrCreate(
                [
                    'course_id' => $courseId,
                    'title' => $quiz->title . ' Questions',
                    'created_by' => $userId,
                ],
                [
                    'description' => 'Questions for quiz: ' . $quiz->title,
                    'status' => 'active',
                ]
            );

            // Create the question
            $question = \App\Models\Question::create([
                'question_bank_id' => $questionBank->id,
                'question_type' => 'multiple_choice',
                'question_text' => $questionData['text'],
                'default_points' => $questionData['points'] ?? 1,
                'difficulty' => 'medium',
                'created_by' => $userId,
                'status' => 'active',
            ]);

            // Create choices
            $correctChoiceIndex = $questionData['correct_choice'];
            foreach ($questionData['choices'] as $index => $choiceText) {
                \App\Models\QuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_text' => $choiceText,
                    'is_correct' => ($index + 1) == $correctChoiceIndex,
                    'position' => $index + 1,
                ]);
            }

            // Attach question to quiz
            $quiz->questions()->attach($question->id, [
                'position' => $position++,
                'points' => $questionData['points'] ?? 1,
            ]);
        }
    }
}
