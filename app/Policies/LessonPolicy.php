<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LessonPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('lessons.view');
    }

    public function view(User $user, Lesson $lesson): bool
    {
        if (! $user->hasPermission('lessons.view')) {
            return false;
        }

        $courseId = $lesson?->module?->course_id;

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $courseId)->exists();
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('course_id', $courseId)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('lessons.create')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->exists();
        }

        return false;
    }

    public function update(User $user, Lesson $lesson): bool
    {
        if (! $user->hasPermission('lessons.update')) {
            return false;
        }

        $courseId = $lesson?->module?->course_id;

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $courseId)->exists();
        }

        return false;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        if (! $user->hasPermission('lessons.delete')) {
            return false;
        }

        $courseId = $lesson?->module?->course_id;

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $courseId)->exists();
        }

        return false;
    }

    public function publish(User $user, Lesson $lesson): bool
    {
        if (! $user->hasPermission('lessons.publish')) {
            return false;
        }

        $courseId = $lesson?->module?->course_id;

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $courseId)->exists();
        }

        return false;
    }

    public function reorder(User $user, Lesson $lesson): bool
    {
        if (! $user->hasPermission('lessons.reorder')) {
            return false;
        }

        $courseId = $lesson?->module?->course_id;

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $courseId)->exists();
        }

        return false;
    }
}
