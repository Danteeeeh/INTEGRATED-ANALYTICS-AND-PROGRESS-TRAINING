<?php

namespace App\Policies;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ClassModel $class): bool
    {
        if (! $user->hasPermission('classes.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $class->instructor_id === $user->id;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('classes.id', $class->id)->exists();
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('classes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('classes.create');
    }

    public function update(User $user, ClassModel $class): bool
    {
        if (! $user->hasPermission('classes.update')) {
            return false;
        }

        // Admin can update any class
        if ($user->isAdmin()) {
            return true;
        }

        // Instructor can only update their assigned classes
        if ($user->isInstructor()) {
            return $class->instructor_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, ClassModel $class): bool
    {
        if (! $user->hasPermission('classes.delete')) {
            return false;
        }

        // Only admin can delete classes
        return $user->isAdmin();
    }

    public function assignInstructor(User $user, ClassModel $class): bool
    {
        return $user->hasPermission('classes.update') && $user->isAdmin();
    }

    public function viewRoster(User $user, ClassModel $class): bool
    {
        // Admin can view any roster
        if ($user->isAdmin()) {
            return true;
        }

        // Instructor can view their class roster
        if ($user->isInstructor()) {
            return $class->instructor_id === $user->id;
        }

        return false;
    }

    public function viewPerformance(User $user, ClassModel $class): bool
    {
        // Admin can view any performance
        if ($user->isAdmin()) {
            return true;
        }

        // Instructor can view their class performance
        if ($user->isInstructor()) {
            return $class->instructor_id === $user->id;
        }

        return false;
    }
}
