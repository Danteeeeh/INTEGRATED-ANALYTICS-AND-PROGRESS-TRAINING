<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\BadgeAward;
use App\Models\ClassModel;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BadgeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Badge::class);

        $query = Badge::with(['course', 'class', 'creator']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('badge_type')) {
            $query->where('badge_type', $request->badge_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('criteria_description', 'like', "%{$search}%");
            });
        }

        $badges = $query->orderByDesc('created_at')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.badges.index', compact('badges', 'courses', 'classes'));
    }

    public function create(): View
    {
        $this->authorize('create', Badge::class);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.badges.create', compact('courses', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Badge::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'badge_type' => 'required|in:course,competency,achievement,participation',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'criteria' => 'nullable|array',
            'criteria_description' => 'nullable|string',
            'status' => 'required|in:draft,active,archived',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['issued_count'] = 0;

        $badge = Badge::create($validated);

        session()->flash('success', 'Badge created successfully.');

        return redirect()->route('admin.badges.show', $badge);
    }

    public function show(Badge $badge): View
    {
        $this->authorize('view', $badge);

        $badge->load(['course', 'class', 'creator', 'awards.student', 'awards.awardedBy']);

        return view('admin.badges.show', compact('badge'));
    }

    public function edit(Badge $badge): View
    {
        $this->authorize('update', $badge);

        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.badges.edit', compact('badge', 'courses', 'classes'));
    }

    public function update(Request $request, Badge $badge): RedirectResponse
    {
        $this->authorize('update', $badge);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'badge_type' => 'required|in:course,competency,achievement,participation',
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'criteria' => 'nullable|array',
            'criteria_description' => 'nullable|string',
            'status' => 'required|in:draft,active,archived',
        ]);

        $badge->update($validated);

        session()->flash('success', 'Badge updated successfully.');

        return redirect()->route('admin.badges.show', $badge);
    }

    public function destroy(Badge $badge): RedirectResponse
    {
        $this->authorize('delete', $badge);

        try {
            if ($badge->awards()->exists()) {
                return back()->with('error', 'Cannot delete a badge that has been awarded to students.');
            }
            $badge->delete();
            session()->flash('success', 'Badge archived successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.badges.index');
    }

    public function award(Request $request, Badge $badge): RedirectResponse
    {
        $this->authorize('update', $badge);

        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
            'award_note' => 'nullable|string|max:500',
        ]);

        $awarded = 0;
        $awardedBy = $request->user()->id;
        $awardedAt = now();

        foreach ($validated['student_ids'] as $studentId) {
            $existing = BadgeAward::where('badge_id', $badge->id)
                ->where('student_id', $studentId)
                ->first();

            if (! $existing) {
                BadgeAward::create([
                    'badge_id' => $badge->id,
                    'student_id' => $studentId,
                    'issued_by' => $awardedBy,
                    'issued_at' => $awardedAt,
                    'award_reason' => $validated['award_note'] ?? null,
                ]);
                $awarded++;
            }
        }

        if ($awarded > 0) {
            $badge->increment('issued_count', $awarded);
        }

        session()->flash('success', "Badge awarded to {$awarded} student(s) successfully.");

        return back();
    }
}
