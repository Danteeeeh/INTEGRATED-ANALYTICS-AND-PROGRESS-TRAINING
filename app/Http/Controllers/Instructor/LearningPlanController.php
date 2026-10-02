<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\LearningPlan;
use App\Models\LearningPlanItem;
use App\Models\User;
use App\Services\LearningPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearningPlanController extends Controller
{
    public function __construct(private LearningPlanService $learningPlanService) {}

    /**
     * List students in a class with their learning-plan status.
     */
    public function index(ClassModel $class): View
    {
        $this->authorize('view', $class);

        $class->load(['course']);

        // Reuse the same at-risk rule the instructor dashboard uses:
        // active enrollments with a final grade below 60 or not yet graded.
        $enrollments = Enrollment::where('class_id', $class->id)
            ->where('status', 'active')
            ->with(['student', 'student.learningPlans' => fn ($q) => $q->with('items')])
            ->orderBy('enrolled_at', 'desc')
            ->get();

        $students = $enrollments->map(function (Enrollment $enrollment) {
            $plan = $enrollment->student?->learningPlans
                ?->sortByDesc('created_at')
                ?->first();

            return [
                'enrollment' => $enrollment,
                'student' => $enrollment->student,
                'plan' => $plan,
                'is_at_risk' => $enrollment->final_grade === null || (float) $enrollment->final_grade < 60,
            ];
        });

        return view('instructor.learning-plans.index', [
            'class' => $class,
            'students' => $students,
            'activeNav' => 'learning_plans',
        ]);
    }

    /**
     * Generate (or fetch) a learning plan for a student in this class.
     */
    public function store(ClassModel $class, User $student): RedirectResponse
    {
        $this->authorize('view', $class);

        $enrolled = Enrollment::where('class_id', $class->id)
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 422, 'That student is not actively enrolled in this class.');

        $plan = $this->learningPlanService->generateForStudent($student);

        return redirect()
            ->route('instructor.classes.learning-plans.index', $class)
            ->with('success', 'Learning plan ready for '.$student->name.'.');
    }

    /**
     * Edit plan details or an individual item status from the instructor side.
     */
    public function update(Request $request, LearningPlan $learningPlan): RedirectResponse
    {
        Gate::authorize('update', $learningPlan);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,active,completed',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|integer',
            'items.*.status' => 'nullable|in:pending,in_progress,completed',
            'items.*.title' => 'nullable|string|max:255',
        ]);

        if (isset($validated['title']) || isset($validated['description']) || isset($validated['status'])) {
            $learningPlan->update(array_filter([
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? null,
            ], fn ($v) => $v !== null));
        }

        foreach (($validated['items'] ?? []) as $itemData) {
            $item = isset($itemData['id'])
                ? LearningPlanItem::find($itemData['id'])
                : null;

            if (! $item || $item->learning_plan_id !== $learningPlan->id) {
                continue;
            }

            $item->update(array_filter([
                'status' => $itemData['status'] ?? null,
                'title' => $itemData['title'] ?? null,
            ], fn ($v) => $v !== null));
        }

        return redirect()
            ->route('instructor.classes.index')
            ->with('success', 'Learning plan updated.');
    }
}
