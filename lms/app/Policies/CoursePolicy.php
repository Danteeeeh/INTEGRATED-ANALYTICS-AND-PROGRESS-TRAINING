<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePolicy
{
    use HandlesAuthorization;

    /**
     * Administrators retain institutional access even if a legacy role-permission
     * pivot is stale or incomplete.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Course $course): bool
    {
        if (! $user->hasPermission('courses.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $course->id)->exists()
                || $course->created_by === $user->id;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('course_id', $course->id)->exists();
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('courses.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('courses.create');
    }

    public function update(User $user, Course $course): bool
    {
        if (! $user->hasPermission('courses.update')) {
            return false;
        }

        // Admin can update any course
        if ($user->isAdmin()) {
            return true;
        }

        // Instructor can only update their assigned courses
        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $course->id)->exists();
        }

        return false;
    }

    public function delete(User $user, Course $course): bool
    {
        if (! $user->hasPermission('courses.delete')) {
            return false;
        }

        // Only admin can delete courses
        return $user->isAdmin();
    }

    public function publish(User $user, Course $course): bool
    {
        return $user->hasPermission('courses.publish') && $user->isAdmin();
    }
}
