<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradeCategory;
use App\Models\GradeHistory;
use App\Models\GradeItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradebookController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Grade::class);

        $query = ClassModel::with(['course', 'instructor', 'enrollments.student', 'gradeCategories.items']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->filled('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('course', function ($qc) use ($search) {
                        $qc->where('title', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $classes = $query->orderBy('code')->paginate(15);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);

        return view('admin.gradebook.index', compact('classes', 'courses'));
    }

    public function classView(Request $request, ClassModel $class): View
    {
        $this->authorize('view', Grade::class);

        $class->load([
            'course',
            'instructor',
            'gradeCategories.items',
            'enrollments.student',
        ]);

        $students = $class->enrollments()->with('student')->where('status', 'active')->get()->pluck('student');

        $gradeItems = GradeItem::whereHas('category', function ($q) use ($class) {
            $q->where('class_id', $class->id);
        })->with('category')->orderBy('position')->get();

        $grades = Grade::whereHas('item.category', function ($q) use ($class) {
            $q->where('class_id', $class->id);
        })->get()->keyBy(function ($g) {
            return $g->grade_item_id.'-'.$g->student_id;
        });

        $categories = GradeCategory::where('class_id', $class->id)->with('items')->orderBy('position')->get();

        $releaseStatus = $request->get('release_status');

        return view('admin.gradebook.class-view', compact(
            'class',
            'students',
            'gradeItems',
            'grades',
            'categories',
            'releaseStatus'
        ));
    }

    public function releaseGrades(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('update', Grade::class);

        $validated = $request->validate([
            'grade_item_ids' => 'nullable|array',
            'grade_item_ids.*' => 'exists:grade_items,id',
            'release_all' => 'boolean',
            'action' => 'required|in:release,unrelease',
        ]);

        $query = GradeItem::whereHas('category', function ($q) use ($class) {
            $q->where('class_id', $class->id);
        });

        if (! $request->boolean('release_all', false) && $request->filled('grade_item_ids')) {
            $query->whereIn('id', $validated['grade_item_ids']);
        }

        $gradeItems = $query->get();
        $count = 0;

        foreach ($gradeItems as $item) {
            $released = $validated['action'] === 'release';
            $item->update(['is_released' => $released]);
            $count++;
        }

        $action = $validated['action'] === 'release' ? 'released' : 'unreleased';

        session()->flash('success', "Grades {$action} for {$count} grade item(s) successfully.");

        return back();
    }

    public function gradeHistory(Request $request): View
    {
        $this->authorize('view', GradeHistory::class);

        $query = GradeHistory::with(['grade.item.category.class.course', 'grade.student', 'changedBy']);

        if ($request->filled('class_id')) {
            $query->whereHas('grade.item.category.class', function ($q) use ($request) {
                $q->where('id', $request->class_id);
            });
        }

        if ($request->filled('course_id')) {
            $query->whereHas('grade.item.category.class.course', function ($q) use ($request) {
                $q->where('id', $request->course_id);
            });
        }

        if ($request->filled('student_id')) {
            $query->whereHas('grade', function ($q) use ($request) {
                $q->where('student_id', $request->student_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('changed_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('changed_at', '<=', $request->date_to);
        }

        $gradeHistory = $query->orderByDesc('changed_at')->paginate(20);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.gradebook.grade-history', compact('gradeHistory', 'courses', 'classes'));
    }
}
