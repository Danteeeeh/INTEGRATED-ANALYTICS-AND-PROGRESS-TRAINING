<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\Exam;
use App\Models\Assignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LearningMaterial::class);

        $query = LearningMaterial::with(['related', 'creator'])
            ->where('created_by', auth()->id());

        if ($request->filled('material_type')) {
            $query->where('material_type', $request->material_type);
        }

        if ($request->filled('related_type')) {
            $query->where('related_type', $request->related_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $materials = $query->orderBy('position')->orderByDesc('created_at')->paginate(15);

        return view('instructor.learning-materials.index', compact('materials'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', LearningMaterial::class);

        $relatedType = $request->input('related_type');
        $relatedId = $request->input('related_id');

        $relatedItem = null;
        if ($relatedType && $relatedId) {
            switch ($relatedType) {
                case Quiz::class:
                    $relatedItem = Quiz::find($relatedId);
                    break;
                case Exam::class:
                    $relatedItem = Exam::find($relatedId);
                    break;
                case Assignment::class:
                    $relatedItem = Assignment::find($relatedId);
                    break;
            }
        }

        return view('instructor.learning-materials.create', compact('relatedType', 'relatedId', 'relatedItem'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LearningMaterial::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'material_type' => 'required|in:file,link,video,document,pdf,image,audio',
            'file' => 'nullable|file|max:'.config('lms.uploads.learning_material'),
            'url' => 'nullable|url|max:500',
            'related_type' => 'required|in:App\Models\Quiz,App\Models\Exam,App\Models\Assignment',
            'related_id' => 'required|integer',
            'position' => 'nullable|integer|min:0',
            'is_required' => 'boolean',
            'access_until' => 'nullable|date',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_required'] = $request->boolean('is_required', false);
        $validated['position'] = $validated['position'] ?? 0;

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
            $validated['file_mime_type'] = $file->getMimeType();

            $path = $file->store('learning-materials', 'public');
            $validated['file_path'] = $path;
        }

        $material = LearningMaterial::create($validated);

        session()->flash('success', 'Learning material added successfully.');

        return redirect()->back();
    }

    public function show(LearningMaterial $learningMaterial): View
    {
        $this->authorize('view', $learningMaterial);

        if ($learningMaterial->created_by !== auth()->id()) {
            abort(403);
        }

        $learningMaterial->load(['related', 'creator']);

        return view('instructor.learning-materials.show', compact('learningMaterial'));
    }

    public function edit(LearningMaterial $learningMaterial): View
    {
        $this->authorize('update', $learningMaterial);

        if ($learningMaterial->created_by !== auth()->id()) {
            abort(403);
        }

        $learningMaterial->load('related');

        return view('instructor.learning-materials.edit', compact('learningMaterial'));
    }

    public function update(Request $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->authorize('update', $learningMaterial);

        if ($learningMaterial->created_by !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'material_type' => 'required|in:file,link,video,document,pdf,image,audio',
            'file' => 'nullable|file|max:'.config('lms.uploads.learning_material'),
            'url' => 'nullable|url|max:500',
            'position' => 'nullable|integer|min:0',
            'is_required' => 'boolean',
            'access_until' => 'nullable|date',
        ]);

        $validated['is_required'] = $request->boolean('is_required', $learningMaterial->is_required);
        $validated['position'] = $validated['position'] ?? $learningMaterial->position;

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
            $validated['file_mime_type'] = $file->getMimeType();

            // Delete old file if exists
            if ($learningMaterial->file_path) {
                \Storage::disk('public')->delete($learningMaterial->file_path);
            }

            $path = $file->store('learning-materials', 'public');
            $validated['file_path'] = $path;
        }

        $learningMaterial->update($validated);

        session()->flash('success', 'Learning material updated successfully.');

        return redirect()->route('instructor.learning_materials.show', $learningMaterial);
    }

    public function destroy(LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->authorize('delete', $learningMaterial);

        if ($learningMaterial->created_by !== auth()->id()) {
            abort(403);
        }

        try {
            // Delete file if exists
            if ($learningMaterial->file_path) {
                \Storage::disk('public')->delete($learningMaterial->file_path);
            }

            $learningMaterial->delete();
            session()->flash('success', 'Learning material deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('update', LearningMaterial::class);

        $validated = $request->validate([
            'materials' => 'required|array',
            'materials.*.id' => 'required|integer|exists:learning_materials,id',
            'materials.*.position' => 'required|integer|min:0',
        ]);

        foreach ($validated['materials'] as $item) {
            $material = LearningMaterial::find($item['id']);
            if ($material && $material->created_by === auth()->id()) {
                $material->update(['position' => $item['position']]);
            }
        }

        session()->flash('success', 'Materials reordered successfully.');

        return redirect()->back();
    }
}
