<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Test Bank's "Questions" tab (§1, §6).
 *
 * One searchable list across every question the instructor may use — their own
 * banks plus anything an admin shared — instead of having to open each bank in
 * turn to find a topic.
 *
 * Editing and deleting stay with each bank (Instructor\QuestionBankController) so
 * a question always has an owner and a subject. Creating is offered here too, so
 * a new question with its answer key can be written without first walking
 * through a bank's long edit page — but it is written straight into the chosen
 * bank, which is the single place a question lives.
 */
class QuestionLibraryController extends Controller
{
    /**
     * Create a question (and its answer key) straight from the Test Bank list.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'question_bank_id' => 'required|integer|exists:question_banks,id',
            'question_type' => 'required|string',
            'question_text' => 'required|string|max:5000',
            'explanation' => 'nullable|string|max:5000',
            'difficulty' => 'required|string|in:easy,medium,hard',
            'default_points' => 'required|numeric|min:0|max:1000',
            'tags' => 'nullable|string|max:255',
            'status' => 'required|string|in:draft,active,archived',
            'category_id' => 'nullable|integer|exists:question_categories,id',
            'is_case_sensitive' => 'nullable',
            'new' => 'nullable|array',
            'new.*.text' => 'nullable|string|max:1000',
            'new.*.correct' => 'nullable',
        ]);

        $bank = QuestionBank::findOrFail($data['question_bank_id']);

        // Only banks this user may write to. Shared banks owned by somebody
        // else stay read-only, so a question can never be parked in a bank the
        // author cannot maintain.
        $writable = $bank->created_by === $user->id
            || ($user->isAdmin() && $bank->is_shared);

        abort_unless($writable, 403, 'You can only add questions to a bank you own.');

        // Reuse the bank's own rules so the library form and the bank form
        // accept and reject exactly the same questions.
        $payload = app(QuestionBankController::class)->buildQuestionPayload($request);

        QuestionBankController::persistQuestion($bank, $payload, $user->id);

        return redirect()
            ->route(
                $user->isAdmin() ? 'admin.test_bank.index' : 'instructor.test_bank.index',
                array_filter(['bank_id' => $bank->id])
            )
            ->with('success', "Question added to \"{$bank->title}\".");
    }
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Question::class);

        $user = auth()->user();

        $query = Question::query();

        // §16 — an admin manages every bank; an instructor sees only their own
        // plus what an admin shared. Never anyone else's private work.
        if (! $user->isAdmin()) {
            $query->whereHas('bank', fn ($q) => $q
                ->where('created_by', $user->id)
                ->orWhere('is_shared', true));
        }

        $query
            ->with([
                'bank:id,title,course_id,class_id,created_by,is_shared',
                'category:id,name,course_id',
                'creator:id,first_name,last_name',
                'choices',
            ])
            ->withCount(['quizzes', 'exams']);

        // §6 — subject (course/class).
        if ($request->filled('course_id')) {
            $query->whereHas('bank', fn ($q) => $q->where('course_id', $request->integer('course_id')));
        }

        if ($request->filled('class_id')) {
            $query->whereHas('bank', fn ($q) => $q->where('class_id', $request->integer('class_id')));
        }

        if ($request->filled('bank_id')) {
            $query->where('question_bank_id', $request->integer('bank_id'));
        }

        // §7 — topic category.
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        // §2/§3 — type, difficulty, status.
        if ($request->filled('question_type')) {
            $query->where('question_type', $request->string('question_type'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->string('difficulty'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } elseif (! $request->boolean('include_inactive')) {
            // Archived questions stay searchable but sit behind a toggle rather
            // than cluttering the default view (§3).
            $query->where('status', '!=', Question::STATUS_ARCHIVED);
        }

        // §6 — creator, and mine/shared scoping.
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->integer('created_by'));
        }

        if ($request->filled('scope')) {
            $query->when(
                $request->scope === 'mine',
                fn ($q) => $q->where('created_by', $user->id),
                fn ($q) => $q->when(
                    $request->scope === 'shared',
                    fn ($qq) => $qq->whereHas('bank', fn ($b) => $b->where('is_shared', true))
                )
            );
        }

        // §6 — free text over the question itself, its explanation and tags.
        if ($request->filled('q')) {
            $needle = '%'.mb_strtolower(trim((string) $request->q)).'%';

            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(question_text) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(explanation, "")) LIKE ?', [$needle]);
            });
        }

        $questions = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $categories = QuestionCategory::orderBy('name')->get(['id', 'name', 'course_id']);
        $banks = QuestionBank::where('created_by', $user->id)
            ->orWhere('is_shared', true)
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('instructor.question-banks.questions', [
            'questions' => $questions,
            'courses' => $courses,
            'categories' => $categories,
            'banks' => $banks,
            'creators' => $this->visibleCreators(),
        ]);
    }

    /**
     * §4 — see the question exactly as a student would meet it, with the answer
     * key hidden unless the viewer is allowed to see it.
     */
    public function preview(Question $question): View
    {
        $this->authorize('view', $question);

        $question->load(['choices' => fn ($q) => $q->orderBy('position'), 'bank', 'category', 'creator']);

        return view('instructor.question-banks.preview', [
            'question' => $question,
            // Everyone who reaches this screen has passed the role gate
            // (role:instructor / role:admin) plus QuestionPolicy::view, and the
            // policy refuses students outright — so the key is safe to show.
            // Keeping the flag explicit lets the view drop it without touching
            // the rest of the layout if that ever changes.
            'isCorrectVisible' => true,
        ]);
    }

    /**
     * Distinct creator ids present in the visible set, for the filter dropdown.
     *
     * @return array<int, User>
     */
    private function visibleCreators(): array
    {
        $user = auth()->user();

        return User::whereIn('id', function ($q) use ($user) {
            $q->select('created_by')->from('questions')->whereIn('question_bank_id', function ($qq) use ($user) {
                $qq->select('id')->from('question_banks')
                    ->where('created_by', $user->id)
                    ->orWhere('is_shared', true);
            });
        })->orderBy('first_name')->get()->all();
    }
}
