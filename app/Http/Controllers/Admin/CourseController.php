<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Department;
use App\Models\Program;
use App\Services\AuditService;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseController extends Controller
{
    public function __construct(
        private CourseService $courses,
        private AuditService $audit,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Course::class);

        $filters = request()->only(['status', 'search', 'academic_period_id', 'department_id']);
        
        // If department code is passed, convert it to department_id
        if (request()->filled('department')) {
            $department = Department::where('code', request()->department)->first();
            if ($department) {
                $filters['department_id'] = $department->id;
            }
        }

        $courses = $this->courses->getAllCourses($filters);
        $departments = Department::orderBy('name')->get();

        return view('admin.courses.index', compact('courses', 'departments'));
    }

    public function create(): View
    {
        $this->authorize('create', Course::class);

        $academicPeriods = AcademicPeriod::query()->orderBy('start_date', 'desc')->get();
        $departments = Department::orderBy('name')->get();
        $programs = Program::with('department')->orderBy('code')->get();

        return view('admin.courses.create', compact('academicPeriods', 'departments', 'programs'));
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $this->courses->createCourse($request->validated(), $request->user());

        $this->audit->log($request->user(), 'course.created', Course::class, $course->id, null, $course->toArray(), $request);

        return redirect()->route('admin.courses.index')
            ->with('status', 'Course created successfully.');
    }

    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        $course->load('classes.academicPeriod', 'classes.instructor', 'academicPeriod', 'creator');

        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course): View
    {
        $this->authorize('update', $course);

        $academicPeriods = AcademicPeriod::query()->orderBy('start_date', 'desc')->get();
        $departments = Department::orderBy('name')->get();
        $programs = Program::with('department')->orderBy('code')->get();

        return view('admin.courses.edit', compact('course', 'academicPeriods', 'departments', 'programs'));
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $old = $course->toArray();
        $updated = $this->courses->updateCourse($course->id, $request->validated());

        $this->audit->log($request->user(), 'course.updated', Course::class, $course->id, $old, $updated->toArray(), $request);

        return redirect()->route('admin.courses.index')
            ->with('status', 'Course updated successfully.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        try {
            $this->courses->deleteCourse($course->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->log(request()->user(), 'course.deleted', Course::class, $course->id, $course->toArray(), null, request());

        return redirect()->route('admin.courses.index')
            ->with('status', 'Course archived successfully.');
    }

    public function publish(Course $course): RedirectResponse
    {
        $this->authorize('update', $course);
        $course->update(['status' => 'published']);
        $this->audit->log(request()->user(), 'course.published', Course::class, $course->id, null, ['status' => 'published'], request());

        return back()->with('status', 'Course published successfully.');
    }

    public function unpublish(Course $course): RedirectResponse
    {
        $this->authorize('update', $course);
        $course->update(['status' => 'draft']);
        $this->audit->log(request()->user(), 'course.unpublished', Course::class, $course->id, null, ['status' => 'draft'], request());

        return back()->with('status', 'Course moved back to draft.');
    }

    public function archive(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);
        $course->update(['status' => 'archived']);
        $this->audit->log(request()->user(), 'course.archived', Course::class, $course->id, null, ['status' => 'archived'], request());

        return back()->with('status', 'Course archived successfully.');
    }

    public function duplicate(Course $course): RedirectResponse
    {
        $this->authorize('create', Course::class);

        $baseCode = Str::upper($course->code.'-COPY');
        $code = $baseCode;
        $suffix = 2;
        while (Course::withTrashed()->where('code', $code)->exists()) {
            $code = $baseCode.'-'.$suffix++;
        }

        $copy = $course->replicate();
        $copy->fill([
            'code' => $code,
            'title' => $course->title.' (Copy)',
            'status' => 'draft',
            'created_by' => request()->user()?->id,
        ]);
        $copy->save();

        $this->audit->log(request()->user(), 'course.duplicated', Course::class, $copy->id, null, ['source_course_id' => $course->id], request());

        return redirect()->route('admin.courses.edit', $copy)
            ->with('status', 'Course copy created as a draft.');
    }

    public function quickStats(Course $course): View
    {
        $this->authorize('view', $course);

        $course->load('classes.enrollments', 'modules.lessons', 'assignments', 'quizzes');

        $stats = [
            'total_classes' => $course->classes->count(),
            'active_classes' => $course->classes->where('status', 'active')->count(),
            'total_enrollments' => $course->classes->sum(function ($class) {
                return $class->enrollments->count();
            }),
            'active_enrollments' => $course->classes->sum(function ($class) {
                return $class->enrollments->where('status', 'active')->count();
            }),
            'total_modules' => $course->modules->count(),
            'total_lessons' => $course->modules->sum(function ($module) {
                return $module->lessons->count();
            }),
            'total_assignments' => $course->assignments->count(),
            'total_quizzes' => $course->quizzes->count(),
            'completion_rate' => $this->calculateCompletionRate($course),
        ];

        return view('admin.courses.stats', compact('course', 'stats'));
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:publish,unpublish,archive,delete',
            'course_ids' => 'required|array',
            'course_ids.*' => 'exists:courses,id',
        ]);

        $count = 0;
        foreach ($request->course_ids as $courseId) {
            $course = Course::findOrFail($courseId);
            $this->authorize($validated['action'] === 'delete' ? 'delete' : 'update', $course);

            switch ($validated['action']) {
                case 'publish':
                    $course->update(['status' => 'published']);
                    break;
                case 'unpublish':
                    $course->update(['status' => 'draft']);
                    break;
                case 'archive':
                    $course->update(['status' => 'archived']);
                    break;
                case 'delete':
                    if (!$course->classes()->exists()) {
                        $course->delete();
                    }
                    break;
            }
            $count++;
        }

        $this->audit->log(request()->user(), 'courses.bulk_'.$validated['action'], Course::class, null, null, ['count' => $count], request());

        return back()->with('status', "Successfully {$validated['action']}ed {$count} courses.");
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Course::class);

        $courses = Course::with(['academicPeriod', 'department', 'program', 'creator'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('academic_period_id'), fn ($q) => $q->where('academic_period_id', $request->academic_period_id))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->orderBy('code')
            ->get();

        $filename = 'courses-export-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($courses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Code', 'Title', 'Description', 'Department', 'Program', 'Academic Period', 'Duration (Weeks)', 'Status', 'Created By', 'Created At']);

            foreach ($courses as $course) {
                fputcsv($handle, [
                    $course->id,
                    $course->code,
                    $course->title,
                    $course->description,
                    $course->department?->name,
                    $course->program?->name,
                    $course->academicPeriod?->name,
                    $course->duration_weeks,
                    $course->status,
                    $course->creator?->full_name,
                    $course->created_at->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function calculateCompletionRate(Course $course): float
    {
        $totalEnrollments = $course->classes->sum(function ($class) {
            return $class->enrollments->count();
        });

        if ($totalEnrollments === 0) {
            return 0;
        }

        $completedEnrollments = $course->classes->sum(function ($class) {
            return $class->enrollments->where('status', 'completed')->count();
        });

        return ($completedEnrollments / $totalEnrollments) * 100;
    }
}
