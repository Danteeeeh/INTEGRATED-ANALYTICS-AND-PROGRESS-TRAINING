<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\SectionModuleAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContentProgressService
{
    /**
     * Memoised assignedModuleIdsForClasses() results, keyed by the class ids.
     * A single dashboard render asks for the same set several times; without
     * this each tile would re-run the section/module lookups.
     *
     * @var array<string, Collection<int, int>>
     */
    private array $assignedModuleCache = [];

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

    /**
     * The modules a set of classes may work through, merged per course.
     *
     * Used by the scoped assignment/quiz queries below so the progress tiles
     * and the dashboard counts only see the work that belongs to the
     * student's own sections. Classes without a section (or sections with no
     * assignments) keep every published module, the same fallback as
     * assignedModuleIds().
     *
     * @return Collection<int, int>
     */
    public function assignedModuleIdsForClasses($classIds): Collection
    {
        $classIds = collect($classIds)->filter()->unique()->values();

        if ($classIds->isEmpty()) {
            return collect();
        }

        $cacheKey = $classIds->sort()->implode(',');

        if (isset($this->assignedModuleCache[$cacheKey])) {
            return $this->assignedModuleCache[$cacheKey];
        }

        $classes = ClassModel::whereIn('classes.id', $classIds)->with('course')->get();

        $moduleIds = collect();

        foreach ($classes as $class) {
            if ($class->course) {
                $moduleIds = $moduleIds->merge($this->assignedModuleIds($class->course, $class->id));
            }
        }

        return $this->assignedModuleCache[$cacheKey] = $moduleIds->unique()->values();
    }

    /**
     * The enrolled classes that belong to a student's own section.
     *
     * Being enrolled is not enough on its own: an active row can point at
     * another block's class (transfers, AA drift, shared demo data). A class
     * stays in scope when it carries no section at all (legacy data) or when
     * its section is the student's own. A student without a section keeps every
     * enrolled class, because there is nothing to narrow against.
     *
     * Anything the student sees about a class — its grades, its modules, its
     * place in search — has to start from this set, otherwise a hidden class
     * leaks back in through one surface at a time.
     *
     * @param  iterable<int, int|string>  $classIds
     * @return Collection<int, int>
     */
    public function sectionScopedClassIds(int $studentId, $classIds): Collection
    {
        $classIds = collect($classIds)->filter()->unique()->values();

        if ($classIds->isEmpty()) {
            return $classIds;
        }

        $sectionId = User::whereKey($studentId)->value('section_id');

        if (! $sectionId) {
            return $classIds;
        }

        return ClassModel::whereIn('id', $classIds)
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $sectionId))
            ->pluck('id');
    }

    /**
     * The classes whose released grades may count for a student.
     *
     * A grade is only theirs when its class is one of their own section's
     * classes; those classes would otherwise drag a student's average away from
     * the courses they actually sit. Shared by the dashboard and My Progress
     * so their average grade cards cannot drift apart.
     *
     * @param  iterable<int, int|string>  $classIds
     * @return Collection<int, int>
     */
    public function sectionScopedGradeClassIds(int $studentId, $classIds): Collection
    {
        return $this->sectionScopedClassIds($studentId, $classIds);
    }

    /**
     * The student's own-section classes for a single course.
     *
     * Every per-course page (course overview, assignment/quiz/exam lists)
     * starts by asking "which of my classes is this course?". Going straight
     * to the raw enrolment list let another block's class supply that scope,
     * so the pages counted and listed work the student had no access to. They
     * now share this lookup, and with it the section rule, so the same course
     * answers the same way whichever page asks.
     *
     * @return Collection<int, int>
     */
    public function sectionScopedClassIdsForCourse(int $studentId, int $courseId): Collection
    {
        $classIds = Enrollment::where('student_id', $studentId)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->whereHas('class', fn ($q) => $q->where('course_id', $courseId))
            ->pluck('class_id');

        return $this->sectionScopedClassIds($studentId, $classIds);
    }

    /**
     * Assignments the student is actually given: class-level work for their
     * classes, plus module/lesson work inside their sections' modules.
     *
     * studentAssignmentsQuery() remains the wider "everything in my classes and
     * courses" base; student-facing surfaces use this narrower one so their
     * numbers can never outgrow the per-course cards.
     */
    public function sectionScopedAssignmentsQuery($classIds, $courseIds): Builder
    {
        $moduleIds = $this->assignedModuleIdsForClasses($classIds);

        return $this->studentAssignmentsQuery($classIds, $courseIds)
            ->where(function ($q) use ($moduleIds) {
                $q->where(function ($q2) {
                    $q2->whereNull('assignments.module_id')->whereNull('assignments.lesson_id');
                })
                    ->orWhereIn('assignments.module_id', $moduleIds)
                    ->orWhereHas('lesson', fn ($q2) => $q2->whereIn('module_id', $moduleIds));
            });
    }

    /**
     * The quiz counterpart of sectionScopedAssignmentsQuery().
     */
    public function sectionScopedQuizzesQuery($classIds, $courseIds): Builder
    {
        $moduleIds = $this->assignedModuleIdsForClasses($classIds);

        return $this->studentQuizzesQuery($classIds, $courseIds)
            ->where(function ($q) use ($moduleIds) {
                $q->where(function ($q2) {
                    $q2->whereNull('quizzes.module_id')->whereNull('quizzes.lesson_id');
                })
                    ->orWhereIn('quizzes.module_id', $moduleIds)
                    ->orWhereHas('lesson', fn ($q2) => $q2->whereIn('module_id', $moduleIds));
            });
    }

    /**
     * The exam counterpart of sectionScopedAssignmentsQuery(): class-level
     * exams for the student's classes, plus module exams inside the modules
     * their section was assigned. An exam sitting in another section's module
     * is not this student's exam, even when the course is the same.
     *
     * studentExamsQuery() also matches on course, which on its own would let a
     * class-level exam from another block's class of the same course through,
     * so a module-less exam additionally has to sit in one of the classes.
     */
    public function sectionScopedExamsQuery($classIds, $courseIds): Builder
    {
        $moduleIds = $this->assignedModuleIdsForClasses($classIds);

        return $this->studentExamsQuery($classIds, $courseIds)
            ->where(function ($q) use ($classIds, $moduleIds) {
                $q->where(function ($q2) use ($classIds) {
                    $q2->whereNull('exams.module_id')->whereIn('exams.class_id', $classIds);
                })
                    ->orWhereIn('exams.module_id', $moduleIds);
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

    /**
     * Consecutive days with a real learning activity — a completed lesson, a
     * finished quiz attempt or a submitted assignment.
     *
     * The dashboard, My Progress, the AI tutor and learning plans each used to
     * count a different thing (lesson updated_at vs lesson completed_at vs
     * lessons only), so the same student could see "1-day streak" on the
     * dashboard and "0 day streak" on My Progress at the same moment.
     */
    public function learningStreak(int $studentId, int $lookbackDays = 30): int
    {
        $streak = 0;
        $today = Carbon::today();

        for ($i = 0; $i < $lookbackDays; $i++) {
            $date = $today->copy()->subDays($i);

            $hasActivity = LessonProgress::where('student_id', $studentId)
                ->where('status', LessonProgress::STATUS_COMPLETED)
                ->whereDate('completed_at', $date)
                ->exists()
                || QuizAttempt::where('student_id', $studentId)
                    ->whereIn('status', [
                        QuizAttempt::STATUS_SUBMITTED,
                        QuizAttempt::STATUS_AUTO_SUBMITTED,
                        QuizAttempt::STATUS_GRADED,
                    ])
                    ->whereDate('created_at', $date)
                    ->exists()
                || AssignmentSubmission::where('student_id', $studentId)
                    ->whereIn('status', [
                        AssignmentSubmission::STATUS_SUBMITTED,
                        AssignmentSubmission::STATUS_GRADED,
                        AssignmentSubmission::STATUS_RETURNED,
                        AssignmentSubmission::STATUS_RESUBMITTED,
                    ])
                    ->whereDate('submitted_at', $date)
                    ->exists();

            if ($hasActivity) {
                $streak++;
            } elseif ($i > 0) {
                // Today may still be in progress without breaking the streak.
                break;
            }
        }

        return $streak;
    }

    /**
     * The published modules a student's section is allowed to work through.
     *
     * Modules can be assigned per section, so a course shared by several
     * sections gives each one a different set. Progress must use that set —
     * counting another section's modules made a student look behind on work
     * that was never theirs.
     *
     * When the section has no assignments at all (older courses) every
     * published module counts, the same fallback the Modules page uses.
     *
     * @return Collection<int, int>
     */
    public function assignedModuleIds(Course $course, ?int $classId = null): Collection
    {
        $publishedIds = $course->modules()->published()->pluck('modules.id');

        if (! $classId) {
            return $publishedIds;
        }

        $sectionId = ClassModel::whereKey($classId)->value('section_id');

        if (! $sectionId) {
            return $publishedIds;
        }

        $assigned = SectionModuleAssignment::active()
            ->byCourse($course->id)
            ->bySection($sectionId)
            ->whereIn('section_module_assignments.module_id', $publishedIds)
            ->pluck('section_module_assignments.module_id')
            ->unique()
            ->values();

        return $assigned->isNotEmpty() ? $assigned : $publishedIds;
    }

    public function calculateCourseLiveProgress(Course $course, int $studentId, ?int $classId = null): array
    {
        // Only the modules assigned to the student's section (or every
        // published module when the section has no assignments).
        $moduleIds = $this->assignedModuleIds($course, $classId);

        $totalModules = $moduleIds->count();
        $completedModules = ModuleProgress::where('student_id', $studentId)
            ->whereIn('module_id', $moduleIds)
            ->where('status', ModuleProgress::STATUS_COMPLETED)
            ->count();
        $modulesPct = $totalModules > 0 ? ($completedModules / $totalModules) * 100 : 0;

        // Count only lessons inside those assigned, published modules — the
        // same set the student can actually open from the Modules page.
        $totalLessons = Lesson::whereIn('module_id', $moduleIds)->published()->count();

        $completedLessons = LessonProgress::where('student_id', $studentId)
            ->whereHas('lesson', function ($q) use ($moduleIds) {
                $q->published()->whereIn('module_id', $moduleIds);
            })
            ->where('status', LessonProgress::STATUS_COMPLETED)
            ->count();
        $lessonsPct = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;

        $courseIds = collect([$course->id]);
        $classIds = $classId ? collect([$classId]) : $course->classes()->pluck('classes.id');

        // Class-level work always counts; module/lesson work only counts when
        // it sits inside one of the student's assigned modules.
        $withinAssignedModules = function ($query) use ($moduleIds) {
            $query->where(function ($q) {
                $q->whereNull('assignments.module_id')->whereNull('assignments.lesson_id');
            })
                ->orWhereIn('assignments.module_id', $moduleIds)
                ->orWhereHas('lesson', fn ($q) => $q->whereIn('module_id', $moduleIds));
        };

        $assignmentsBase = $this->studentAssignmentsQuery($classIds, $courseIds)
            ->where($withinAssignedModules);
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
                // Count each assignment once. A resubmission adds another row
                // for the same assignment; counting rows pushed the category
                // past 100% and skewed the overall progress chart.
                ->distinct('assignment_id')
                ->count('assignment_id')
            : 0;
        $assignmentsPct = $totalAssignments > 0 ? ($completedAssignments / $totalAssignments) * 100 : 0;

        $quizzesBase = $this->studentQuizzesQuery($classIds, $courseIds)
            ->where(function ($query) use ($moduleIds) {
                $query->where(function ($q) {
                    $q->whereNull('quizzes.module_id')->whereNull('quizzes.lesson_id');
                })
                    ->orWhereIn('quizzes.module_id', $moduleIds)
                    ->orWhereHas('lesson', fn ($q) => $q->whereIn('module_id', $moduleIds));
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
        $quizzesPct = $totalQuizzes > 0 ? ($completedQuizzes / $totalQuizzes) * 100 : 0;

        // Only average the categories that actually have content. Dividing by a
        // fixed 4 punished courses that have no assignments or no quizzes —
// a course with only modules and lessons could never exceed 50%.
        $components = [];

        if ($totalModules > 0) {
            $components[] = $modulesPct;
        }

        if ($totalLessons > 0) {
            $components[] = $lessonsPct;
        }

        if ($totalAssignments > 0) {
            $components[] = $assignmentsPct;
        }

        if ($totalQuizzes > 0) {
            $components[] = $quizzesPct;
        }

        $overall = $components
            ? array_sum($components) / count($components)
            : 0;

        return [
            'overall' => (float) round($overall, 2),
            // A course with nothing published has no denominator: its 0% is not
            // "no progress", so callers exclude it from averages and print
            // "no content yet" instead of a 0% bar.
            'has_content' => ($totalModules + $totalLessons + $totalAssignments + $totalQuizzes) > 0,
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
