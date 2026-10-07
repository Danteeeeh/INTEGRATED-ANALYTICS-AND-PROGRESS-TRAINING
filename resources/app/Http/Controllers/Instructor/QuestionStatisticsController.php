<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Services\QuestionUsageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Question Statistics" tab (§1, §10, §11).
 *
 * Reports how often each item is used and how well students do on it, and
 * offers a recommendation. It never edits, retires or re-weights anything —
 * §11 forbids letting analytics modify the bank on its own.
 */
class QuestionStatisticsController extends Controller
{
    public function __construct(private QuestionUsageService $usage) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Question::class);

        $user = auth()->user();

        $query = $this->usage->forUser($user);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('question_type')) {
            $query->where('question_type', $request->string('question_type'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->string('difficulty'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('course_id')) {
            $query->whereHas('bank', fn ($q) => $q->where('course_id', $request->integer('course_id')));
        }

        if ($request->filled('q')) {
            $needle = '%'.mb_strtolower(trim((string) $request->q)).'%';
            $query->whereRaw('LOWER(question_text) LIKE ?', [$needle]);
        }

        // §11 — surface the items that need attention first.
        $sort = $request->string('sort')->toString();

        $query->orderByDesc(match ($sort) {
            'usage' => 'quizzes_count',
            'answered' => 'quiz_answers_count',
            default => 'quizzes_count',
        });

        $questions = $query->paginate(25)->withQueryString();

        $this->usage->annotate($questions);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $categories = QuestionCategory::orderBy('name')->get(['id', 'name', 'course_id']);

        return view('instructor.question-banks.statistics', [
            'questions' => $questions,
            'categories' => $categories,
            'courses' => $courses,
        ]);
    }
}
