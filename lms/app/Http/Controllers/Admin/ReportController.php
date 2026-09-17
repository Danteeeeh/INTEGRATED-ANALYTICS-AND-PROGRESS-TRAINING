<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Course::class);

        $totalCourses = Course::count();
        $totalClasses = ClassModel::count();
        $totalStudents = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))->count();
        $totalInstructors = User::whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))->count();
        $totalEnrollments = Enrollment::count();
        $activeEnrollments = Enrollment::active()->count();
        $completedEnrollments = Enrollment::completed()->count();

        return view('admin.reports.index', compact(
            'totalCourses',
            'totalClasses',
            'totalStudents',
            'totalInstructors',
            'totalEnrollments',
            'activeEnrollments',
            'completedEnrollments',
        ));
    }

    public function enrollment(Request $request): View
    {
        $this->authorize('viewAny', Enrollment::class);

        $query = Enrollment::with(['student', 'class.course', 'class.academicPeriod', 'class.instructor']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('academic_period_id')) {
            $query->whereHas('class', fn ($q) => $q->where('academic_period_id', $request->academic_period_id));
        }

        if ($request->filled('course_id')) {
            $query->whereHas('class', fn ($q) => $q->where('course_id', $request->course_id));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('identifier', 'like', "%{$search}%"));
        }

        $enrollments = $query->orderBy('enrolled_at', 'desc')->paginate(20);
        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->get(['id', 'name', 'code']);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.reports.enrollment', compact('enrollments', 'academicPeriods', 'courses', 'classes'));
    }

    public function courseCompletion(Request $request): View
    {
        $this->authorize('viewAny', Course::class);

        $query = CourseCompletion::with(['class.course', 'class.academicPeriod', 'student', 'certificate']);

        if ($request->filled('academic_period_id')) {
            $query->whereHas('class', fn ($q) => $q->where('academic_period_id', $request->academic_period_id));
        }

        if ($request->filled('course_id')) {
            $query->whereHas('class', fn ($q) => $q->where('course_id', $request->course_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $completions = $query->orderByDesc('completed_at')->paginate(20);
        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->get(['id', 'name', 'code']);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);

        return view('admin.reports.course_completion', compact('completions', 'academicPeriods', 'courses'));
    }

    public function studentPerformance(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->with(['role', 'enrollments.class.course', 'enrollments.class.academicPeriod']);

        if ($request->filled('academic_period_id')) {
            $query->whereHas('enrollments.class', fn ($q) => $q->where('academic_period_id', $request->academic_period_id));
        }

        if ($request->filled('class_id')) {
            $query->whereHas('enrollments', fn ($q) => $q->where('class_id', $request->class_id));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('first_name')->orderBy('last_name')->paginate(20);
        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->get(['id', 'name', 'code']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);

        return view('admin.reports.student_performance', compact('students', 'academicPeriods', 'classes'));
    }

    public function instructorPerformance(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))
            ->with(['role', 'classesInstructing.course', 'classesInstructing.academicPeriod', 'classesInstructing.enrollments']);

        if ($request->filled('academic_period_id')) {
            $query->whereHas('classesInstructing', fn ($q) => $q->where('academic_period_id', $request->academic_period_id));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $instructors = $query->orderBy('first_name')->orderBy('last_name')->paginate(20);
        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->get(['id', 'name', 'code']);

        return view('admin.reports.instructor_performance', compact('instructors', 'academicPeriods'));
    }

    public function attendance(Request $request): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $query = AttendanceRecord::with(['class.course', 'class.academicPeriod', 'student', 'virtualClass', 'recordedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('academic_period_id')) {
            $query->whereHas('class', fn ($q) => $q->where('academic_period_id', $request->academic_period_id));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('attendance_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('attendance_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $attendanceRecords = $query->orderBy('attendance_date', 'desc')->orderBy('created_at', 'desc')->paginate(30);
        $academicPeriods = AcademicPeriod::orderBy('start_date', 'desc')->get(['id', 'name', 'code']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.reports.attendance', compact('attendanceRecords', 'academicPeriods', 'classes', 'students'));
    }

    public function gradeDistribution(Request $request): View
    {
        $this->authorize('viewAny', Grade::class);

        $query = Grade::with(['item.gradeCategory.class.course', 'student', 'gradedBy']);

        if ($request->filled('class_id')) {
            $query->whereHas('item.gradeCategory', fn ($q) => $q->where('class_id', $request->class_id));
        }

        if ($request->filled('course_id')) {
            $query->whereHas('item.gradeCategory.class', fn ($q) => $q->where('course_id', $request->course_id));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('letter_grade')) {
            $query->where('letter_grade', $request->letter_grade);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $grades = $query->orderByDesc('graded_at')->paginate(30);
        $courses = Course::orderBy('code')->get(['id', 'code', 'title']);
        $classes = ClassModel::with('course')->orderBy('code')->get(['id', 'code', 'course_id']);
        $students = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.reports.grade_distribution', compact('grades', 'courses', 'classes', 'students'));
    }

    public function export(Request $request, ?string $type = null): StreamedResponse
    {
        $this->authorize('viewAny', Course::class);

        $reportType = $type ?? $request->input('type', 'enrollment');
        $timestamp = now()->format('Ymd-His');
        $filename = "report-{$reportType}-{$timestamp}.csv";

        return response()->streamDownload(function () use ($reportType, $request) {
            $handle = fopen('php://output', 'w');
            $chunkSize = config('lms.export_chunk_size');

            switch ($reportType) {
                case 'enrollment':
                    fputcsv($handle, ['ID', 'Student', 'Student Email', 'Class Code', 'Course', 'Academic Period', 'Instructor', 'Status', 'Final Grade', 'Enrolled At', 'Completed At']);
                    Enrollment::with(['student', 'class.course', 'class.academicPeriod', 'class.instructor'])
                        ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                        ->when($request->filled('academic_period_id'), fn ($q) => $q->whereHas('class', fn ($cq) => $cq->where('academic_period_id', $request->academic_period_id)))
                        ->when($request->filled('course_id'), fn ($q) => $q->whereHas('class', fn ($cq) => $cq->where('course_id', $request->course_id)))
                        ->when($request->filled('search'), function ($q) use ($request) {
                            $search = trim((string) $request->input('search'));
                            $q->whereHas('student', fn ($sq) => $sq
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('identifier', 'like', "%{$search}%"));
                        })
                        ->orderBy('enrolled_at', 'desc')
                        ->chunk($chunkSize, function ($chunk) use ($handle) {
                            foreach ($chunk as $e) {
                                fputcsv($handle, [
                                    $e->id,
                                    $e->student?->full_name,
                                    $e->student?->email,
                                    $e->class?->code,
                                    $e->class?->course?->title,
                                    $e->class?->academicPeriod?->name,
                                    $e->class?->instructor?->full_name,
                                    $e->status,
                                    $e->final_grade,
                                    $e->enrolled_at?->toDateTimeString(),
                                    $e->completed_at?->toDateTimeString(),
                                ]);
                            }
                        });
                    break;

                case 'course-completion':
                    fputcsv($handle, ['ID', 'Student', 'Student Email', 'Class Code', 'Course', 'Completion %', 'Final Grade', 'Completed At', 'Certificate']);
                    CourseCompletion::with(['class.course', 'class.academicPeriod', 'student', 'certificate'])
                        ->when($request->filled('academic_period_id'), fn ($q) => $q->whereHas('class', fn ($cq) => $cq->where('academic_period_id', $request->academic_period_id)))
                        ->when($request->filled('course_id'), fn ($q) => $q->whereHas('class', fn ($cq) => $cq->where('course_id', $request->course_id)))
                        ->when($request->filled('search'), function ($q) use ($request) {
                            $search = trim((string) $request->input('search'));
                            $q->whereHas('student', fn ($sq) => $sq
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"));
                        })
                        ->orderByDesc('completed_at')
                        ->chunk($chunkSize, function ($chunk) use ($handle) {
                            foreach ($chunk as $c) {
                                fputcsv($handle, [
                                    $c->id,
                                    $c->student?->full_name,
                                    $c->student?->email,
                                    $c->class?->code,
                                    $c->class?->course?->title,
                                    $c->completion_percent,
                                    $c->final_grade,
                                    $c->completed_at?->toDateTimeString(),
                                    $c->certificate?->code ?? '-',
                                ]);
                            }
                        });
                    break;

                case 'student-performance':
                    fputcsv($handle, ['Student ID', 'Name', 'Email', 'Identifier', 'Status', 'Enrollments (Active)', 'Enrollments (Completed)', 'Avg Final Grade']);
                    User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
                        ->with(['enrollments.class.course'])
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->chunk($chunkSize, function ($chunk) use ($handle) {
                            foreach ($chunk as $s) {
                                $activeCount = $s->enrollments->where('status', 'active')->count();
                                $completedCount = $s->enrollments->where('status', 'completed')->count();
                                $avgGrade = $s->enrollments->where('status', 'completed')->avg('final_grade');
                                fputcsv($handle, [
                                    $s->id,
                                    $s->full_name,
                                    $s->email,
                                    $s->identifier,
                                    $s->status,
                                    $activeCount,
                                    $completedCount,
                                    $avgGrade ? number_format($avgGrade, 2) : '-',
                                ]);
                            }
                        });
                    break;

                case 'instructor-performance':
                    fputcsv($handle, ['Instructor ID', 'Name', 'Email', 'Status', 'Classes Taught', 'Total Students', 'Avg Class Enrollment']);
                    User::whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))
                        ->with(['classesInstructing.enrollments', 'classesInstructing.course'])
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->chunk($chunkSize, function ($chunk) use ($handle) {
                            foreach ($chunk as $i) {
                                $classesCount = $i->classesInstructing->count();
                                $totalStudents = $i->classesInstructing->sum(fn ($c) => $c->enrollments->count());
                                $avgEnrollment = $classesCount > 0 ? number_format($totalStudents / $classesCount, 1) : '0';
                                fputcsv($handle, [
                                    $i->id,
                                    $i->full_name,
                                    $i->email,
                                    $i->status,
                                    $classesCount,
                                    $totalStudents,
                                    $avgEnrollment,
                                ]);
                            }
                        });
                    break;

                case 'attendance':
                    fputcsv($handle, ['ID', 'Date', 'Session Title', 'Class Code', 'Course', 'Student', 'Status', 'Joined At', 'Left At', 'Duration (min)', 'Notes', 'Recorded By']);
                    AttendanceRecord::with(['class.course', 'student', 'recordedBy'])
                        ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                        ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->class_id))
                        ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
                        ->when($request->filled('from_date'), fn ($q) => $q->whereDate('attendance_date', '>=', $request->from_date))
                        ->when($request->filled('to_date'), fn ($q) => $q->whereDate('attendance_date', '<=', $request->to_date))
                        ->orderBy('attendance_date', 'desc')
                        ->chunk($chunkSize, function ($chunk) use ($handle) {
                            foreach ($chunk as $a) {
                                fputcsv($handle, [
                                    $a->id,
                                    $a->attendance_date?->toDateString(),
                                    $a->session_title,
                                    $a->class?->code,
                                    $a->class?->course?->title,
                                    $a->student?->full_name,
                                    $a->status,
                                    $a->joined_at?->toDateTimeString(),
                                    $a->left_at?->toDateTimeString(),
                                    $a->duration_minutes,
                                    $a->notes,
                                    $a->recordedBy?->full_name,
                                ]);
                            }
                        });
                    break;

                case 'grade-distribution':
                    fputcsv($handle, ['ID', 'Student', 'Class Code', 'Category', 'Grade Item', 'Points', 'Score %', 'Letter Grade', 'Graded By', 'Graded At', 'Feedback']);
                    Grade::with(['item.gradeCategory.class', 'student', 'gradedBy'])
                        ->when($request->filled('class_id'), fn ($q) => $q->whereHas('item.gradeCategory', fn ($gq) => $gq->where('class_id', $request->class_id)))
                        ->when($request->filled('course_id'), fn ($q) => $q->whereHas('item.gradeCategory.class', fn ($cq) => $cq->where('course_id', $request->course_id)))
                        ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
                        ->when($request->filled('letter_grade'), fn ($q) => $q->where('letter_grade', $request->letter_grade))
                        ->when($request->filled('search'), function ($q) use ($request) {
                            $search = trim((string) $request->input('search'));
                            $q->whereHas('student', fn ($sq) => $sq
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"));
                        })
                        ->orderByDesc('graded_at')
                        ->chunk($chunkSize, function ($chunk) use ($handle) {
                            foreach ($chunk as $g) {
                                fputcsv($handle, [
                                    $g->id,
                                    $g->student?->full_name,
                                    $g->item?->gradeCategory?->class?->code,
                                    $g->item?->gradeCategory?->name,
                                    $g->item?->name,
                                    $g->points,
                                    $g->score_percent,
                                    $g->letter_grade,
                                    $g->gradedBy?->full_name,
                                    $g->graded_at?->toDateTimeString(),
                                    $g->feedback,
                                ]);
                            }
                        });
                    break;

                default:
                    fputcsv($handle, ['Error'], ['Unknown report type: '.$reportType]);
                    break;
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
