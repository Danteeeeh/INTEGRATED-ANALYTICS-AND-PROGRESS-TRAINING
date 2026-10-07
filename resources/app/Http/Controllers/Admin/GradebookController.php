<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\GradeItem;
use App\Models\Program;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradebookController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Grade::class);

        $query = ClassModel::with(['course', 'instructor', 'enrollments.student', 'gradeItems', 'section.program']);

        if ($request->filled('program_id')) {
            $query->whereHas('section', function ($q) use ($request) {
                $q->where('program_id', $request->program_id);
            });
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

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
        $programs = \App\Models\Program::orderBy('code')->get(['id', 'code', 'name']);
        $sections = $request->filled('program_id')
            ? \App\Models\Section::where('program_id', $request->program_id)->orderBy('code')->get(['id', 'code', 'name'])
            : collect();

        return view('admin.gradebook.index', compact('classes', 'courses', 'programs', 'sections'));
    }

    public function classView(Request $request, ClassModel $class): View
    {
        $this->authorize('view', Grade::class);

        $class->load([
            'course',
            'instructor',
            'gradeItems',
            'enrollments.student',
        ]);

        $students = $class->enrollments()->with('student')->where('status', 'active')->get()->pluck('student');

        $gradeItems = GradeItem::where('class_id', $class->id)->orderBy('position')->get();

        $grades = Grade::whereHas('item', function ($q) use ($class) {
            $q->where('class_id', $class->id);
        })->get()->keyBy(function ($g) {
            return $g->grade_item_id.'-'.$g->student_id;
        });

        $releaseStatus = $request->get('release_status');

        return view('admin.gradebook.class-view', compact(
            'class',
            'students',
            'gradeItems',
            'grades',
            'releaseStatus'
        ));
    }

    public function programView(Request $request): View
    {
        $this->authorize('viewAny', Grade::class);

        $query = Program::with(['sections.classes.course', 'sections.classes.instructor', 'sections.classes.enrollments.student']);

        if ($request->filled('academic_period_id')) {
            $query->whereHas('sections', function ($q) use ($request) {
                $q->where('academic_period_id', $request->academic_period_id);
            });
        }

        $programs = $query->orderBy('code')->get();

        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->get();

        return view('admin.gradebook.program-view', compact('programs', 'academicPeriods'));
    }

    public function sectionView(Request $request, Section $section): View
    {
        $this->authorize('view', Grade::class);

        $section->load([
            'program',
            'classes.course',
            'classes.instructor',
            'classes.enrollments.student',
            'classes.gradeItems',
        ]);

        // Get all students in this section
        $students = User::where('section_id', $section->id)
            ->whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->with(['enrollments.class', 'enrollments.class.gradeItems' => function ($q) {
                $q->orderBy('position');
            }])
            ->get();

        // Organize grades by type (quiz, assignment, exam)
        $organizedGrades = [];
        foreach ($students as $student) {
            $organizedGrades[$student->id] = [
                'student' => $student,
                'quizzes' => [],
                'assignments' => [],
                'exams' => [],
                'other' => [],
            ];

            foreach ($student->enrollments as $enrollment) {
                $class = $enrollment->class;
                foreach ($class->gradeItems as $gradeItem) {
                    $grade = Grade::where('grade_item_id', $gradeItem->id)
                        ->where('student_id', $student->id)
                        ->first();

                    $gradeData = [
                        'grade_item' => $gradeItem,
                        'grade' => $grade,
                        'class' => $class,
                    ];

                    switch ($gradeItem->item_type) {
                        case GradeItem::TYPE_QUIZ:
                            $organizedGrades[$student->id]['quizzes'][] = $gradeData;
                            break;
                        case GradeItem::TYPE_ASSIGNMENT:
                            $organizedGrades[$student->id]['assignments'][] = $gradeData;
                            break;
                        case GradeItem::TYPE_EXAM:
                            $organizedGrades[$student->id]['exams'][] = $gradeData;
                            break;
                        default:
                            $organizedGrades[$student->id]['other'][] = $gradeData;
                            break;
                    }
                }
            }
        }

        return view('admin.gradebook.section-view', compact('section', 'organizedGrades'));
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

        $query = GradeItem::where('class_id', $class->id);

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

    public function gradeStatus(Request $request): View
    {
        $this->authorize('viewAny', Grade::class);

        $query = ClassModel::with(['course', 'instructor'])
            ->withCount([
                'enrollments as active_enrollments' => fn ($q) => $q->where('status', 'active'),
                'enrollments as graded_enrollments' => fn ($q) => $q->where('status', '!=', 'dropped')->whereNotNull('final_grade'),
            ])
            ->orderBy('code');

        if ($request->filled('academic_period_id')) {
            $query->where('academic_period_id', $request->integer('academic_period_id'));
        }

        $classes = $query->paginate(15)->withQueryString();
        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->get();

        return view('admin.gradebook.status', compact('classes', 'academicPeriods'));
    }

    public function classGrades(ClassModel $class): View
    {
        $this->authorize('view', Grade::class);
        $class->load(['course', 'instructor', 'enrollments.student']);

        return view('admin.gradebook.status-class', compact('class'));
    }

    public function returnForCorrection(ClassModel $class): RedirectResponse
    {
        $this->authorize('update', Grade::class);
        $enrollments = Enrollment::where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->whereNotNull('final_grade')
            ->get();

        foreach ($enrollments as $enrollment) {
            $enrollment->update([
                'final_grade' => null,
                'notes' => trim(($enrollment->notes ?? '')."\nGrades returned for correction on ".now()->toDateTimeString()),
            ]);
        }

        return back()->with('status', "Grades for {$class->code} were returned for correction.");
    }

    public function gradeHistory(Request $request): View
    {
        $this->authorize('view', GradeHistory::class);

        $query = GradeHistory::with(['grade.item.class.course', 'grade.student', 'changedBy']);

        if ($request->filled('class_id')) {
            $query->whereHas('grade.item.class', function ($q) use ($request) {
                $q->where('id', $request->class_id);
            });
        }

        if ($request->filled('course_id')) {
            $query->whereHas('grade.item.class.course', function ($q) use ($request) {
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
