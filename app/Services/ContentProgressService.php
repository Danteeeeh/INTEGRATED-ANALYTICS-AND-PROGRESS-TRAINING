<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ContentProgressService
{
    public function markLessonStarted(Lesson $lesson, User $student): LessonProgress
    {
        return DB::transaction(function () use ($lesson, $student) {
            $progress = LessonProgress::firstOrCreate(
                [
                    'lesson_id' => $lesson->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => LessonProgress::STATUS_NOT_STARTED,
                    'progress_percent' => 0,
                ]
            );

            if ($progress->status === LessonProgress::STATUS_NOT_STARTED) {
                $progress->update([
                    'status' => LessonProgress::STATUS_IN_PROGRESS,
                    'started_at' => now(),
                    'last_accessed_at' => now(),
                    'progress_percent' => max($progress->progress_percent, 1),
                ]);
            } else {
                $progress->update(['last_accessed_at' => now()]);
            }

            $this->recalculateModuleProgress($lesson->module, $student);

            $class = $this->findEnrolledClass($lesson->module->course_id, $student);
            if ($class) {
                $this->recalculateCourseProgress($class, $student);
            }

            return $progress->fresh();
        });
    }

    public function markLessonCompleted(Lesson $lesson, User $student): LessonProgress
    {
        return DB::transaction(function () use ($lesson, $student) {
            $progress = LessonProgress::firstOrCreate(
                [
                    'lesson_id' => $lesson->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => LessonProgress::STATUS_NOT_STARTED,
                    'progress_percent' => 0,
                ]
            );

            $progress->update([
                'status' => LessonProgress::STATUS_COMPLETED,
                'started_at' => $progress->started_at ?? now(),
                'completed_at' => now(),
                'last_accessed_at' => now(),
                'progress_percent' => 100,
            ]);

            $this->recalculateModuleProgress($lesson->module, $student);

            $class = $this->findEnrolledClass($lesson->module->course_id, $student);
            if ($class) {
                $this->recalculateCourseProgress($class, $student);
            }

            return $progress->fresh();
        });
    }

    public function recalculateModuleProgress(Module $module, User $student): void
    {
        DB::transaction(function () use ($module, $student) {
            $lessons = $module->lessons()->published()->get();
            $totalLessons = $lessons->count();

            $completedIds = LessonProgress::where('student_id', $student->id)
                ->whereIn('lesson_id', $lessons->pluck('id'))
                ->where('status', LessonProgress::STATUS_COMPLETED)
                ->pluck('lesson_id');

            $lessonsCompleted = $completedIds->count();
            $progressPercent = $totalLessons > 0 ? round(($lessonsCompleted / $totalLessons) * 100, 2) : 0;

            $status = ModuleProgress::STATUS_NOT_STARTED;
            if ($lessonsCompleted > 0 && $lessonsCompleted < $totalLessons) {
                $status = ModuleProgress::STATUS_IN_PROGRESS;
            } elseif ($lessonsCompleted === $totalLessons && $totalLessons > 0) {
                $status = ModuleProgress::STATUS_COMPLETED;
            }

            $startedAt = null;
            $anyStarted = LessonProgress::where('student_id', $student->id)
                ->whereIn('lesson_id', $lessons->pluck('id'))
                ->whereNotNull('started_at')
                ->orderBy('started_at', 'asc')
                ->first();
            if ($anyStarted) {
                $startedAt = $anyStarted->started_at;
            }

            $completedAt = null;
            if ($status === ModuleProgress::STATUS_COMPLETED) {
                $lastCompleted = LessonProgress::where('student_id', $student->id)
                    ->whereIn('lesson_id', $lessons->pluck('id'))
                    ->whereNotNull('completed_at')
                    ->orderBy('completed_at', 'desc')
                    ->first();
                $completedAt = $lastCompleted?->completed_at;
            }

            ModuleProgress::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'student_id' => $student->id,
                ],
                [
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                    'lessons_completed' => $lessonsCompleted,
                    'total_lessons' => $totalLessons,
                    'progress_percent' => $progressPercent,
                    'status' => $status,
                ]
            );
        });
    }

    public function recalculateCourseProgress(ClassModel $class, User $student): void
    {
        DB::transaction(function () use ($class, $student) {
            $course = $class->course;
            $modules = Module::where('course_id', $course->id)->published()->with('lessons')->get();

            $totalModules = $modules->count();
            $totalLessons = 0;
            $modulesCompleted = 0;
            $lessonsCompleted = 0;

            foreach ($modules as $module) {
                $publishedLessons = $module->lessons()->published()->get();
                $lessonCount = $publishedLessons->count();
                $totalLessons += $lessonCount;

                $moduleProgress = ModuleProgress::where('module_id', $module->id)
                    ->where('student_id', $student->id)
                    ->first();

                if ($moduleProgress) {
                    $lessonsCompleted += $moduleProgress->lessons_completed;
                    if ($moduleProgress->status === ModuleProgress::STATUS_COMPLETED) {
                        $modulesCompleted++;
                    }
                }
            }

            $progressPercent = 0;
            if ($totalLessons > 0) {
                $progressPercent = round(($lessonsCompleted / $totalLessons) * 100, 2);
            } elseif ($totalModules > 0) {
                $progressPercent = round(($modulesCompleted / $totalModules) * 100, 2);
            }

            $status = CourseProgress::STATUS_NOT_STARTED;
            if (($modulesCompleted > 0 || $lessonsCompleted > 0) &&
                ! (($totalModules > 0 && $modulesCompleted === $totalModules) || ($totalLessons > 0 && $lessonsCompleted === $totalLessons))
            ) {
                $status = CourseProgress::STATUS_IN_PROGRESS;
            } elseif (($totalModules > 0 && $modulesCompleted === $totalModules) ||
                ($totalLessons > 0 && $lessonsCompleted === $totalLessons)
            ) {
                $status = CourseProgress::STATUS_COMPLETED;
            }

            $startedAt = null;
            $firstModuleStart = ModuleProgress::where('student_id', $student->id)
                ->whereIn('module_id', $modules->pluck('id'))
                ->whereNotNull('started_at')
                ->orderBy('started_at', 'asc')
                ->first();
            if ($firstModuleStart) {
                $startedAt = $firstModuleStart->started_at;
            }

            $completedAt = null;
            if ($status === CourseProgress::STATUS_COMPLETED) {
                $lastModuleComplete = ModuleProgress::where('student_id', $student->id)
                    ->whereIn('module_id', $modules->pluck('id'))
                    ->whereNotNull('completed_at')
                    ->orderBy('completed_at', 'desc')
                    ->first();
                $completedAt = $lastModuleComplete?->completed_at;
            }

            CourseProgress::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                ],
                [
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                    'modules_completed' => $modulesCompleted,
                    'total_modules' => $totalModules,
                    'lessons_completed' => $lessonsCompleted,
                    'total_lessons' => $totalLessons,
                    'progress_percent' => $progressPercent,
                    'status' => $status,
                ]
            );
        });
    }

    private function findEnrolledClass(int $courseId, User $student): ?ClassModel
    {
        return $student->enrolledClasses()
            ->where('course_id', $courseId)
            ->wherePivot('status', 'active')
            ->first();
    }

    public function studentAssignmentsQuery($classIds, $courseIds): Builder
    {
        return Assignment::published()
            ->where(function ($q) use ($classIds, $courseIds) {
                $q->whereIn('assignments.class_id', $classIds)
                    ->orWhere(function ($q2) use ($courseIds) {
                        $q2->whereHas('module', function ($q3) use ($courseIds) {
                            $q3->whereIn('course_id', $courseIds)->published();
                        });
                    })
                    ->orWhere(function ($q2) use ($courseIds) {
                        $q2->whereHas('lesson.module', function ($q3) use ($courseIds) {
                            $q3->whereIn('course_id', $courseIds)->published();
                        });
                    });
            });
    }

    public function studentQuizzesQuery($classIds, $courseIds): Builder
    {
        return Quiz::published()
            ->where(function ($q) use ($classIds, $courseIds) {
                $q->whereIn('quizzes.class_id', $classIds)
                    ->orWhere(function ($q2) use ($courseIds) {
                        $q2->whereHas('module', function ($q3) use ($courseIds) {
                            $q3->whereIn('course_id', $courseIds)->published();
                        });
                    })
                    ->orWhere(function ($q2) use ($courseIds) {
                        $q2->whereHas('lesson.module', function ($q3) use ($courseIds) {
                            $q3->whereIn('course_id', $courseIds)->published();
                        });
                    });
            });
    }

    public function studentExamsQuery($classIds, $courseIds): Builder
    {
        return Exam::published()
            ->where(function ($q) use ($classIds, $courseIds) {
                $q->whereIn('exams.class_id', $classIds)
                    ->orWhereIn('exams.course_id', $courseIds)
                    ->orWhere(function ($q2) use ($courseIds) {
                        $q2->whereHas('module', function ($q3) use ($courseIds) {
                            $q3->whereIn('course_id', $courseIds)->published();
                        });
                    });
            });
    }

    public function calculateCourseLiveProgress(Course $course, int $studentId, ?int $classId = null): array
    {
        $totalModules = $course->modules()->published()->count();
        $completedModules = ModuleProgress::where('student_id', $studentId)
            ->whereHas('module', function ($q) use ($course) {
                $q->where('course_id', $course->id)->published();
            })
            ->where('status', ModuleProgress::STATUS_COMPLETED)
            ->count();
        $modulesPct = $totalModules > 0 ? ($completedModules / $totalModules) * 100 : 0;

        $totalLessons = $course->lessons()->published()->count();
        $completedLessons = LessonProgress::where('student_id', $studentId)
            ->whereHas('lesson', function ($q) use ($course) {
                $q->published()->whereHas('module', function ($q2) use ($course) {
                    $q2->where('course_id', $course->id)->published();
                });
            })
            ->where('status', LessonProgress::STATUS_COMPLETED)
            ->count();
        $lessonsPct = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

        $courseIds = collect([$course->id]);
        $classIds = $classId ? collect([$classId]) : $course->classes()->pluck('classes.id');
        $assignmentsBase = $this->studentAssignmentsQuery($classIds, $courseIds);
        $totalAssignments = (clone $assignmentsBase)->count();
        $completedAssignments = $totalAssignments > 0
            ? AssignmentSubmission::where('student_id', $studentId)
                ->whereIn('assignment_id', (clone $assignmentsBase)->pluck('assignments.id'))
                ->whereIn('status', [
                    AssignmentSubmission::STATUS_SUBMITTED,
                    AssignmentSubmission::STATUS_GRADED,
                    AssignmentSubmission::STATUS_RETURNED,
                    AssignmentSubmission::STATUS_RESUBMITTED,
                ])
                ->count()
            : 0;
        $assignmentsPct = $totalAssignments > 0 ? ($completedAssignments / $totalAssignments) * 100 : 0;

        $quizzesBase = $this->studentQuizzesQuery($classIds, $courseIds);
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
        $quizzesPct = $totalQuizzes > 0 ? ($completedQuizzes / $totalQuizzes) * 100 : 0;

        $hasAny = $totalModules > 0 || $totalLessons > 0 || $totalAssignments > 0 || $totalQuizzes > 0;
        $overall = $hasAny ? ($modulesPct + $lessonsPct + $assignmentsPct + $quizzesPct) / 4 : 0;

        return [
            'overall' => (float) round($overall, 2),
            'modules' => (float) round($modulesPct, 2),
            'lessons' => (float) round($lessonsPct, 2),
            'assignments' => (float) round($assignmentsPct, 2),
            'quizzes' => (float) round($quizzesPct, 2),
            'totals' => [
                'modules' => $totalModules,
                'lessons' => $totalLessons,
                'assignments' => $totalAssignments,
                'quizzes' => $totalQuizzes,
            ],
            'completed' => [
                'modules' => $completedModules,
                'lessons' => $completedLessons,
                'assignments' => $completedAssignments,
                'quizzes' => $completedQuizzes,
            ],
        ];
    }
}
