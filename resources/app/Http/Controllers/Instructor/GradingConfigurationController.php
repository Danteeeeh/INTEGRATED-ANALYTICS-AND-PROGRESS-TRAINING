<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\GradeConfiguration;
use App\Models\GradeItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The instructor-facing "Grading Configuration" screen.
 *
 * The instructor ticks the components they grade and gives each a percentage.
 * Weights must total exactly 100 before the configuration is usable — an
 * unfinished configuration is still saved so the work is not lost, but
 * GradeService ignores it until it balances.
 */
class GradingConfigurationController extends Controller
{
    public function edit(Request $request, ClassModel $class): View
    {
        $this->authorizeSubject($request, $class);

        $configuration = GradeConfiguration::firstOrNew(['class_id' => $class->id]);

        // Component availability so the screen can explain why a checkbox is
        // disabled instead of silently dropping it.
        $available = GradeItem::where('class_id', $class->id)
            ->select('item_type')
            ->selectRaw('count(*) as total')
            ->groupBy('item_type')
            ->pluck('total', 'item_type');

        return view('instructor.gradebook.grading-config', [
            'class' => $class,
            'configuration' => $configuration,
            'components' => GradeConfiguration::COMPONENTS,
            'available' => $available,
            'requiredTotal' => GradeConfiguration::REQUIRED_TOTAL,
        ]);
    }

    public function update(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorizeSubject($request, $class);

        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $weights = [];
        $unknown = [];

        foreach ($request->input('weights', []) as $type => $value) {
            if (! isset(GradeConfiguration::COMPONENTS[$type])) {
                $unknown[] = $type;

                continue;
            }

            $weights[$type] = max(0, min(100, (float) $value));
        }

        if ($unknown !== []) {
            return back()
                ->withInput()
                ->with('error', 'Unknown component: '.implode(', ', $unknown).'.');
        }

        $attributes = ['class_id' => $class->id, 'updated_by' => $request->user()->id];

        foreach (GradeConfiguration::COMPONENTS as $type => $meta) {
            $attributes[$meta['column']] = $weights[$type] ?? 0.0;
        }

        $configuration = GradeConfiguration::updateOrCreate(
            ['class_id' => $class->id],
            $attributes + ['created_by' => $request->user()->id],
        );

        if (! $configuration->isValid()) {
            // Saved, but explicitly not applied. The gradebook keeps using the
            // previous weighting until this balances.
            return back()
                ->withInput()
                ->with('error', $configuration->validationMessage());
        }

        return back()->with('status', 'Grading configuration saved. The gradebook now uses these weights.');
    }

    /**
     * Drop back to the original per-item weighting.
     */
    public function destroy(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorizeSubject($request, $class);

        GradeConfiguration::where('class_id', $class->id)->delete();

        return back()->with('status', 'Grading configuration removed. Grades now use the per-item weights.');
    }

    protected function authorizeSubject(Request $request, ClassModel $class): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        abort_unless($user->isInstructor() && $class->instructor_id === $user->id, 403);
    }
}