<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

/**
 * Test Bank categories (§7).
 *
 * Categories live on the question, not on the bank, because the quota builder
 * (§9) has to be able to say "10 Hardware, 15 Networking" *inside* one subject.
 */
class QuestionCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuestionCategory::class);

        $user = auth()->user();

        $query = QuestionCategory::withCount(['questions as questions_count', 'questions as active_questions_count'
            => fn ($q) => $q->where('status', Question::STATUS_ACTIVE)])
            ->with('creator', 'course')
            ->orderBy('name');

        // §16 — admins see every category; instructors see the ones they made
        // plus the shared (course-less) pool everyone draws on.
        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)->orWhereNull('course_id');
            });
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        } elseif ($request->filled('scope') && $request->scope === 'global') {
            $query->whereNull('course_id');
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(fn ($qq) => $qq
                ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhereRaw('LOWER(COALESCE(description, "")) LIKE ?', ['%'.mb_strtolower($search).'%']));
        }

        if ($request->filled('scope') && $request->scope === 'mine') {
            $query->where('created_by', $user->id);
        }

        $categories = $query->paginate(20)->withQueryString();

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);

        return view('instructor.question-banks.categories', compact('categories', 'courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', QuestionCategory::class);

        $validated = $this->validatePayload($request);

        $category = QuestionCategory::create($validated + [
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', "Category \"{$category->name}\" created.");
    }

    public function update(Request $request, QuestionCategory $questionCategory): RedirectResponse
    {
        $this->authorize('update', $questionCategory);

        $validated = $this->validatePayload($request, $questionCategory);

        $questionCategory->update($validated);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(QuestionCategory $questionCategory): RedirectResponse
    {
        $this->authorize('delete', $questionCategory);

        // Deleting a category that questions still point at would leave the bank
        // unfilterable, and the questions themselves lose their grouping. Retire
        // the questions first instead (§5).
        $inUse = $questionCategory->questions()->count();

        if ($inUse > 0) {
            return back()->with(
                'error',
                "Cannot delete \"{$questionCategory->name}\" — {$inUse} question(s) still use it. Reassign them first."
            );
        }

        $questionCategory->delete();

        // Role-aware: the same controller serves /admin/... and /instructor/...,
        // so a fixed name here would bounce an admin into a route they cannot open.
        $prefix = auth()->user()->isAdmin() ? 'admin.' : 'instructor.';

        return redirect()->route($prefix.'question_banks.categories.index')
            ->with('success', 'Category deleted.');
    }

    /**
     * Uniqueness is checked against live rows here rather than by a database
     * constraint: the index has to exclude soft-deleted rows, and NULL
     * course_ids are distinct under every engine's UNIQUE rule.
     *
     * @return array{name: string, description: string|null, course_id: int|null}
     */
    private function validatePayload(Request $request, ?QuestionCategory $existing = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $name = trim($validated['name']);
        $courseId = $validated['course_id'] ?? null;

        $clash = QuestionCategory::query()
            ->when(
                $courseId === null,
                fn ($q) => $q->whereNull('course_id'),
                fn ($q) => $q->where('course_id', $courseId)
            )
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();

        if ($clash) {
            $scope = $courseId === null ? 'the shared list' : 'this course';

            throw ValidationException::withMessages([
                'name' => "A category named \"{$name}\" already exists in {$scope}.",
            ]);
        }

        return [
            'name' => $name,
            'description' => $validated['description'] ?? null,
            'course_id' => $courseId,
        ];
    }
}
