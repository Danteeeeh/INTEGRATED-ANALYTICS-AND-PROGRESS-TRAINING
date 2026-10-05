<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\LessonProgress;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\VirtualClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(): View
    {
        $studentId = auth()->id();

        // Get available classes (active, not full, student not already enrolled)
        $enrolledClassIds = Enrollment::where('student_id', $studentId)
            ->where('status', '!=', 'dropped')
            ->pluck('class_id')
            ->toArray();

        $availableClasses = ClassModel::active()
            ->whereNotIn('id', $enrolledClassIds)
            ->with('course', 'academicPeriod', 'instructor')
            ->orderBy('code')
            ->get()
            ->filter(function ($class) {
                // Filter out full classes
                $currentEnrollments = Enrollment::where('class_id', $class->id)
                    ->where('status', '!=', 'dropped')
                    ->count();

                return ! $class->isFull();
            });

        // Get enrolled classes
        $enrolledClasses = ClassModel::whereIn('id', $enrolledClassIds)
            ->with('course', 'academicPeriod', 'instructor')
            ->orderBy('code')
            ->get();

        return view('student.classes.index', compact('availableClasses', 'enrolledClasses'));
    }

    public function show(ClassModel $class): View
    {
        $this->authorize('view', $class);

        $class->load('course', 'academicPeriod', 'instructor');

        // Check if student is enrolled
        $isEnrolled = Enrollment::where('student_id', auth()->id())
            ->where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->exists();

        // Check enrollment status if enrolled
        $enrollment = null;
        if ($isEnrolled) {
            $enrollment = Enrollment::where('student_id', auth()->id())
                ->where('class_id', $class->id)
                ->first();
        }

        // Check if class is full
        $currentEnrollments = Enrollment::where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->count();
        $isFull = $class->isFull();

        // Get progress data if enrolled
        $progress = [];
        $upcomingActivities = collect();
        $announcements = collect();

        if ($isEnrolled && $enrollment->status === 'active') {
            $studentId = auth()->id();

            // Calculate progress
            $totalModules = $class->course->modules()->count();
            $completedModules = ModuleProgress::where('student_id', $studentId)
                ->whereHas('module', function ($q) use ($class) {
                    $q->where('course_id', $class->course_id);
                })
                ->where('status', ModuleProgress::STATUS_COMPLETED)
                ->count();

            $totalLessons = $class->course->lessons()->count();
            $completedLessons = LessonProgress::where('student_id', $studentId)
                ->whereHas('lesson', function ($q) use ($class) {
                    $q->whereHas('module', function ($q) use ($class) {
                        $q->where('course_id', $class->course_id);
                    });
                })
                ->where('status', LessonProgress::STATUS_COMPLETED)
                ->count();

            $assignmentsBase = Assignment::published()
                ->where(function ($q) use ($class) {
                    $q->where('assignments.class_id', $class->id)
                        ->orWhere(function ($q2) use ($class) {
                            $q2->whereHas('module', function ($q3) use ($class) {
                                $q3->where('course_id', $class->course_id);
                            });
                        })
                        ->orWhere(function ($q2) use ($class) {
                            $q2->whereHas('lesson.module', function ($q3) use ($class) {
                                $q3->where('course_id', $class->course_id);
                            });
                        });
                });
            $totalAssignments = (clone $assignmentsBase)->count();
            $completedAssignments = $totalAssignments > 0
                ? AssignmentSubmission::where('student_id', $studentId)
                    ->whereIn('assignment_id', (clone $assignmentsBase)->pluck('assignments.id'))
                    ->whereIn('status', ['submitted', 'graded', 'returned', 'resubmitted'])
                    ->count()
                : 0;

            $quizzesBase = Quiz::published()
                ->where(function ($q) use ($class) {
                    $q->where('quizzes.class_id', $class->id)
                        ->orWhere(function ($q2) use ($class) {
                            $q2->whereHas('module', function ($q3) use ($class) {
                                $q3->where('course_id', $class->course_id);
                            });
                        })
                        ->orWhere(function ($q2) use ($class) {
                            $q2->whereHas('lesson.module', function ($q3) use ($class) {
                                $q3->where('course_id', $class->course_id);
                            });
                        });
                });
            $totalQuizzes = (clone $quizzesBase)->count();
            $completedQuizzes = $totalQuizzes > 0
                ? QuizAttempt::where('student_id', $studentId)
                    ->whereIn('quiz_id', (clone $quizzesBase)->pluck('quizzes.id'))
                    ->whereIn('status', [
                        QuizAttempt::STATUS_SUBMITTED,
                        QuizAttempt::STATUS_AUTO_SUBMITTED,
                        QuizAttempt::STATUS_GRADED,
                    ])
                    ->distinct('quiz_id')
                    ->count('quiz_id')
                : 0;

            // Calculate current grade
            $grades = Grade::where('student_id', $studentId)
                ->whereHas('item', function ($q) use ($class) {
                    $q->where('class_id', $class->id)
                        ->where('is_released', true);
                })
                ->get();

            $currentGrade = $grades->isNotEmpty() ? $grades->avg('score_percent') : 0;

            // Calculate overall progress
            $overallProgress = 0;
            if ($totalModules > 0 || $totalLessons > 0 || $totalAssignments > 0 || $totalQuizzes > 0) {
                $moduleProgress = ($totalModules > 0) ? ($completedModules / $totalModules) * 100 : 0;
                $lessonProgress = ($totalLessons > 0) ? ($completedLessons / $totalLessons) * 100 : 0;
                $assignmentProgress = ($totalAssignments > 0) ? ($completedAssignments / $totalAssignments) * 100 : 0;
                $quizProgress = ($totalQuizzes > 0) ? ($completedQuizzes / $totalQuizzes) * 100 : 0;

                $overallProgress = ($moduleProgress + $lessonProgress + $assignmentProgress + $quizProgress) / 4;
            }

            $progress = [
                'overall' => $overallProgress,
                'modules_completed' => $completedModules,
                'lessons_completed' => $completedLessons,
                'assignments_completed' => $completedAssignments,
                'quizzes_completed' => $completedQuizzes,
                'current_grade' => $currentGrade,
            ];

            // Get upcoming activities
            $upcomingAssignments = (clone $assignmentsBase)
                ->where('due_date', '>', now())
                ->orderBy('due_date')
                ->limit(3)
                ->get()
                ->map(function ($assignment) use ($class) {
                    return [
                        'title' => $assignment->title,
                        'type' => 'Assignment',
                        'date' => $assignment->due_date->format('M d, Y g:i A'),
                        'url' => route('student.courses.assignments.show', [$class->course, $assignment]),
                    ];
                });

            $upcomingQuizzes = (clone $quizzesBase)
                ->where('availability_from', '>', now())
                ->orderBy('availability_from')
                ->limit(3)
                ->get()
                ->map(function ($quiz) use ($class) {
                    return [
                        'title' => $quiz->title,
                        'type' => 'Quiz',
                        'date' => $quiz->availability_from->format('M d, Y g:i A'),
                        'url' => route('student.courses.quizzes.show', [$class->course, $quiz]),
                    ];
                });

            $upcomingVirtualClasses = $class->virtualClasses()
                ->where('meeting_date', '>=', now()->startOfDay())
                ->orderBy('meeting_date')
                ->limit(3)
                ->get()
                ->map(function ($virtualClass) use ($class) {
                    return [
                        'title' => $virtualClass->title,
                        'type' => 'Virtual Class',
                        'date' => $virtualClass->meeting_date->format('M d, Y').' '.$virtualClass->start_time,
                        'url' => route('student.classes.virtual_classes.show', [$class, $virtualClass]),
                    ];
                });

            $upcomingActivities = $upcomingAssignments->concat($upcomingQuizzes)->concat($upcomingVirtualClasses)
                ->sortBy('date')
                ->take(5);

            // Get recent announcements
            $announcements = $class->announcements()
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        }

        return view('student.classes.show', compact('class', 'isEnrolled', 'enrollment', 'isFull', 'progress', 'upcomingActivities', 'announcements'));
    }

    public function enroll(Request $request, ClassModel $class)
    {
        $this->authorize('enroll', $class);

        $studentId = auth()->id();

        // Check if already enrolled
        $existing = Enrollment::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->first();

        if ($existing) {
            return back()->with('error', 'You are already enrolled in this class.');
        }

        // Check if class is active
        if (! $class->is_active) {
            return back()->with('error', 'This class is not currently accepting enrollments.');
        }

        // Check class capacity
        $currentEnrollments = Enrollment::where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->count();

        if ($class->isFull()) {
            return back()->with('error', 'This class is already at full capacity.');
        }

        Enrollment::create([
            'student_id' => $studentId,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        return redirect()->route('student.classes.show', $class)
            ->with('status', 'Successfully enrolled in the class!');
    }

    public function drop(ClassModel $class)
    {
        $this->authorize('drop', $class);

        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->where('status', '!=', 'dropped')
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'You are not enrolled in this class.');
        }

        $enrollment->drop();

        return redirect()->route('student.classes.index')
            ->with('status', 'You have dropped the class.');
    }

    public function calendar(): View
    {
        $studentId = auth()->id();

        // Get student's active enrollments
        $enrollments = Enrollment::where('student_id', $studentId)
            ->where('status', 'active')
            ->with(['class.course', 'class.instructor', 'class.academicPeriod'])
            ->get();

        $classIds = $enrollments->pluck('class_id');
        $courseIds = $enrollments->pluck('class.course_id')->filter();

        // Get upcoming assignments
        $assignments = Assignment::published()
            ->where(function ($q) use ($classIds, $courseIds) {
                $q->whereIn('assignments.class_id', $classIds)
                    ->orWhere(function ($q2) use ($courseIds) {
                        $q2->whereHas('module', function ($q3) use ($courseIds) {
                            $q3->whereIn('course_id', $courseIds);
                        });
                    })
                    ->orWhere(function ($q2) use ($courseIds) {
                        $q2->whereHas('lesson.module', function ($q3) use ($courseIds) {
                            $q3->whereIn('course_id', $courseIds);
                        });
                    });
            })
            ->where('due_date', '>=', now()->startOfMonth())
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->orderBy('due_date')
            ->get();

        // Get upcoming quizzes
        $quizzes = Quiz::published()
            ->where(function ($q) use ($classIds, $courseIds) {
                $q->whereIn('quizzes.class_id', $classIds)
                    ->orWhere(function ($q2) use ($courseIds) {
                        $q2->whereHas('module', function ($q3) use ($courseIds) {
                            $q3->whereIn('course_id', $courseIds);
                        });
                    })
                    ->orWhere(function ($q2) use ($courseIds) {
                        $q2->whereHas('lesson.module', function ($q3) use ($courseIds) {
                            $q3->whereIn('course_id', $courseIds);
                        });
                    });
            })
            ->where('availability_from', '>=', now()->startOfMonth())
            ->with(['class.course', 'module.course', 'lesson.module.course'])
            ->orderBy('availability_from')
            ->get();

        // Get virtual classes
        $virtualClasses = VirtualClass::whereIn('class_id', $classIds)
            ->where('meeting_date', '>=', now()->startOfMonth())
            ->with('class.course')
            ->orderBy('meeting_date')
            ->orderBy('start_time')
            ->get();

        // Get course announcements
        $announcements = \App\Models\Announcement::where(function ($q) use ($courseIds, $classIds) {
            $q->whereIn('course_id', $courseIds)
                ->orWhereIn('class_id', $classIds);
        })
            ->where('publish_at', '>=', now()->startOfMonth())
            ->with(['course', 'class'])
            ->orderBy('publish_at')
            ->get();

        // Combine all events
        $events = collect();

        foreach ($assignments as $assignment) {
            $course = $assignment->class?->course
                ?? $assignment->module?->course
                ?? $assignment->lesson?->module?->course;
            $classCode = $assignment->class?->code ?? '';
            $events->push([
                'title' => $assignment->title,
                'type' => 'assignment',
                'date' => $assignment->due_date->format('Y-m-d'),
                'time' => $assignment->due_date->format('H:i'),
                'course' => $course?->title ?? '',
                'class' => $classCode,
                'url' => $course ? route('student.courses.assignments.show', [$course, $assignment]) : '#',
            ]);
        }

        foreach ($quizzes as $quiz) {
            $course = $quiz->class?->course
                ?? $quiz->module?->course
                ?? $quiz->lesson?->module?->course;
            $classCode = $quiz->class?->code ?? '';
            $events->push([
                'title' => $quiz->title,
                'type' => 'quiz',
                'date' => $quiz->availability_from->format('Y-m-d'),
                'time' => $quiz->availability_from->format('H:i'),
                'course' => $course?->title ?? '',
                'class' => $classCode,
                'url' => $course ? route('student.courses.quizzes.show', [$course, $quiz]) : '#',
            ]);
        }

        foreach ($virtualClasses as $virtualClass) {
            $events->push([
                'title' => $virtualClass->title ?? 'Virtual Class',
                'type' => 'virtual_class',
                'date' => $virtualClass->meeting_date->format('Y-m-d'),
                'time' => $virtualClass->start_time,
                'course' => $virtualClass->class->course->title,
                'class' => $virtualClass->class->code,
                'url' => route('student.classes.virtual_classes.show', [$virtualClass->class, $virtualClass]),
            ]);
        }

        foreach ($announcements as $announcement) {
            $events->push([
                'title' => $announcement->title,
                'type' => 'announcement',
                'date' => $announcement->publish_at->format('Y-m-d'),
                'time' => $announcement->publish_at->format('H:i'),
                'course' => $announcement->course->title ?? '',
                'class' => $announcement->class->code ?? '',
                'url' => $announcement->course_id
                    ? route('student.courses.announcements.show', [$announcement->course, $announcement])
                    : route('student.classes.show', $announcement->class),
            ]);
        }

        // Sort events by date and time
        $events = $events->sortBy(function ($event) {
            return $event['date'].$event['time'];
        })->values();

        // Group events by date
        $eventsByDate = $events->groupBy('date');

        return view('student.calendar', compact(
            'events',
            'eventsByDate',
            'enrollments'
        ));
    }
}
