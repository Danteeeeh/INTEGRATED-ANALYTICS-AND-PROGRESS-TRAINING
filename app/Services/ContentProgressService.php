<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\CourseProgress;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\User;
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
}
