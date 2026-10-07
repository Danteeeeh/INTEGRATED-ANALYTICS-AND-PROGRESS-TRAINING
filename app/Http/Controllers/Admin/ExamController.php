<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(
        protected ExamService $examService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Exam::class);

        $query = Exam::with(['class.course', 'creator']);

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
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('instructions', 'like', "%{$search}%");
            });
        }

        $exams = $query->orderByDesc('created_at')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.exams.index', compact('exams', 'courses', 'classes'));
    }

    public function create(): View
    {
        $this->authorize('create', Exam::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.exams.create', compact('courses', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Exam::class);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'nullable|exists:courses,id',
            'module_id' => 'nullable|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'exam_type' => 'required|in:'.implode(',', \App\Models\Exam::acceptedTypes()),
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
            'status' => 'required|in:draft,published,closed',
            // Proctoring
            'requires_proctoring' => 'boolean',
            'proctoring_method' => 'nullable|in:in_person,online,ai_proctor,hybrid',
            'proctoring_instructions' => 'nullable|string',
            'record_session' => 'boolean',
            'detect_tab_switch' => 'boolean',
            'detect_copy_paste' => 'boolean',
        ]);

        $validated['created_by'] = $request->user()->id;
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

        return redirect()->route('admin.exams.show', $exam);
    }

    public function show(Exam $exam): View
    {
        $this->authorize('view', $exam);

        $exam->load(['class.course', 'module', 'creator', 'questions.choices', 'attempts.student']);

        return view('admin.exams.show', compact('exam'));
    }

    public function edit(Exam $exam): View
    {
        $this->authorize('update', $exam);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.exams.edit', compact('exam', 'courses', 'classes'));
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'nullable|exists:courses,id',
            'module_id' => 'nullable|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'exam_type' => 'required|in:'.implode(',', \App\Models\Exam::acceptedTypes()),
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

        return redirect()->route('admin.exams.show', $exam);
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorize('delete', $exam);

        try {
            if ($exam->attempts()->exists()) {
                return back()->with('error', 'Cannot delete an exam that has attempts.');
            }
            $this->examService->deleteExam($exam);
            session()->flash('success', 'Exam archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.exams.index');
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $exam = $this->examService->publishExam($exam);

        session()->flash('success', 'Exam published successfully.');

        return back();
    }

    public function close(Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $exam = $this->examService->closeExam($exam);

        session()->flash('success', 'Exam closed successfully.');

        return back();
    }
}
