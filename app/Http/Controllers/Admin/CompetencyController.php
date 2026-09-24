<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competency;
use App\Models\CompetencyFramework;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetencyController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Competency::class);

        $query = Competency::with(['framework', 'parent', 'children', 'courseMappings.course']);

        if ($request->filled('framework_id')) {
            $query->where('framework_id', $request->framework_id);
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $competencies = $query->orderBy('framework_id')->orderBy('position')->orderBy('id')->paginate(15);
        $frameworks = CompetencyFramework::orderBy('name')->get(['id', 'name']);

        return view('admin.competencies.index', compact('competencies', 'frameworks'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Competency::class);

        $frameworks = CompetencyFramework::orderBy('name')->get(['id', 'name']);
        $competencies = Competency::orderBy('name')->get(['id', 'name', 'framework_id']);
        $preselectedFrameworkId = $request->get('framework_id');
        $preselectedParentId = $request->get('parent_id');

        return view('admin.competencies.create', compact(
            'frameworks',
            'competencies',
            'preselectedFrameworkId',
            'preselectedParentId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Competency::class);

        $validated = $request->validate([
            'framework_id' => 'required|exists:competency_frameworks,id',
            'parent_id' => 'nullable|exists:competencies,id',
            'code' => 'required|string|max:50|unique:competencies,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'learning_outcomes' => 'nullable|array',
            'mastery_levels' => 'nullable|array',
            'position' => 'nullable|integer|min:0',
        ]);

        $validated['position'] = $validated['position'] ?? Competency::where('framework_id', $validated['framework_id'])->max('position') + 1;

        $competency = Competency::create($validated);

        session()->flash('success', 'Competency created successfully.');

        return redirect()->route('admin.competencies.show', $competency);
    }

    public function show(Competency $competency): View
    {
        $this->authorize('view', $competency);

        $competency->load(['framework', 'parent', 'children.framework', 'courseMappings.course', 'studentRecords.student']);

        return view('admin.competencies.show', compact('competency'));
    }

    public function edit(Competency $competency): View
    {
        $this->authorize('update', $competency);

        $frameworks = CompetencyFramework::orderBy('name')->get(['id', 'name']);
        $competencies = Competency::where('id', '!=', $competency->id)
            ->orderBy('name')
            ->get(['id', 'name', 'framework_id']);

        return view('admin.competencies.edit', compact('competency', 'frameworks', 'competencies'));
    }

    public function update(Request $request, Competency $competency): RedirectResponse
    {
        $this->authorize('update', $competency);

        $validated = $request->validate([
            'framework_id' => 'required|exists:competency_frameworks,id',
            'parent_id' => 'nullable|exists:competencies,id',
            'code' => 'required|string|max:50|unique:competencies,code,'.$competency->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'learning_outcomes' => 'nullable|array',
            'mastery_levels' => 'nullable|array',
            'position' => 'nullable|integer|min:0',
        ]);

        $competency->update($validated);

        session()->flash('success', 'Competency updated successfully.');

        return redirect()->route('admin.competencies.show', $competency);
    }

    public function destroy(Competency $competency): RedirectResponse
    {
        $this->authorize('delete', $competency);

        try {
            if ($competency->children()->exists()) {
                return back()->with('error', 'Cannot delete a competency that has child competencies.');
            }
            if ($competency->courseMappings()->exists()) {
                return back()->with('error', 'Cannot delete a competency that is mapped to courses.');
            }
            if ($competency->studentRecords()->exists()) {
                return back()->with('error', 'Cannot delete a competency that has student records.');
            }
            $competency->delete();
            session()->flash('success', 'Competency archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.competencies.index');
    }
}
