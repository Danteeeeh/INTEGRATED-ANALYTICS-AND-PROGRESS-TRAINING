<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('assignments.view');
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if (! $user->hasPermission('assignments.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $assignment->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('classes.id', $assignment->class_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('assignments.create')) {
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

    public function update(User $user, Assignment $assignment): bool
    {
        if (! $user->hasPermission('assignments.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $assignment->class_id)->exists();
        }

        return false;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        if (! $user->hasPermission('assignments.delete')) {
            return false;
        }

        return $user->isAdmin();
    }

    public function grade(User $user, Assignment $assignment): bool
    {
        if (! $user->hasPermission('assignments.grade')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $assignment->class_id)->exists();
        }

        return false;
    }

    public function submit(User $user, Assignment $assignment): bool
    {
        if (! $user->hasPermission('assignments.submit')) {
            return false;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('classes.id', $assignment->class_id)->exists();
        }

        return false;
    }

    public function resubmit(User $user, Assignment $assignment): bool
    {
        if (! $user->hasPermission('assignments.resubmit')) {
            return false;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('classes.id', $assignment->class_id)->exists();
        }

        return false;
    }

    public function publish(User $user, Assignment $assignment): bool
    {
        if (! $user->hasPermission('assignments.publish')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $assignment->class_id)->exists();
        }

        return false;
    }

    public function close(User $user, Assignment $assignment): bool
    {
        if (! $user->hasPermission('assignments.close')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $assignment->class_id)->exists();
        }

        return false;
    }
}
