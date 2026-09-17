<?php

namespace App\Policies;

use App\Models\Rubric;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RubricPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('rubrics.view');
    }

    public function view(User $user, Rubric $rubric): bool
    {
        if (! $user->hasPermission('rubrics.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $rubric->course_id)->exists()
                || $user->classesInstructing()->where('id', $rubric->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('course_id', $rubric->course_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('rubrics.create')) {
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

    public function update(User $user, Rubric $rubric): bool
    {
        if (! $user->hasPermission('rubrics.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $rubric->course_id)->exists()
                || $user->classesInstructing()->where('id', $rubric->class_id)->exists();
        }

        return false;
    }

    public function delete(User $user, Rubric $rubric): bool
    {
        if (! $user->hasPermission('rubrics.delete')) {
            return false;
        }

        return $user->isAdmin();
    }

    public function attach(User $user, Rubric $rubric): bool
    {
        if (! $user->hasPermission('rubrics.attach')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $rubric->course_id)->exists()
                || $user->classesInstructing()->where('id', $rubric->class_id)->exists();
        }

        return false;
    }

    public function grade(User $user, Rubric $rubric): bool
    {
        if (! $user->hasPermission('rubrics.grade')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $rubric->course_id)->exists()
                || $user->classesInstructing()->where('id', $rubric->class_id)->exists();
        }

        return false;
    }
}
