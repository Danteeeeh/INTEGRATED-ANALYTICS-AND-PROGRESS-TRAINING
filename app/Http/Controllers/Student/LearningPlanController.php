<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningPlan;
use App\Models\LearningPlanItem;
use App\Services\LearningPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearningPlanController extends Controller
{
    public function __construct(private LearningPlanService $learningPlanService) {}

    public function index(): View
    {
        $studentId = auth()->id();

        $plans = LearningPlan::with(['items'])
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.learning-plans.index', [
            'plans' => $plans,
            'activeNav' => 'learning_plans',
        ]);
    }

    public function show(LearningPlan $learningPlan): View
    {
        Gate::authorize('view', $learningPlan);

        return view('student.learning-plans.show', [
            'plan' => $learningPlan->load('items'),
            'activeNav' => 'learning_plans',
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $plan = $this->learningPlanService->generateForStudent($request->user());

        return redirect()
            ->route('student.learning-plans.show', $plan)
            ->with('success', 'Your personalized learning plan is ready.');
    }

    public function updateItem(Request $request, LearningPlan $learningPlan, LearningPlanItem $item): RedirectResponse
    {
        Gate::authorize('view', $learningPlan);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $this->learningPlanService->markItem($request->user(), $item, $validated['status']);

        return redirect()
            ->route('student.learning-plans.show', $learningPlan)
            ->with('success', 'Learning plan updated.');
    }
}