<?php

namespace App\Policies;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ClassModel $class): bool
    {
        // Students don't need explicit permission check for viewing their enrolled classes
        if ($user->isStudent()) {
            return Enrollment::where('student_id', $user->id)->where('class_id', $class->id)->where('status', '!=', 'dropped')->exists();
        }

        if (! $user->hasPermission('classes.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $class->instructor_id === $user->id;
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

    public function index(User $user): bool
    {
        // Admin can view all classes
        if ($user->isAdmin()) {
            return $user->hasPermission('classes.view');
        }

        // Instructor can view their own classes
        if ($user->isInstructor()) {
            return $user->hasPermission('classes.view');
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

    public function enroll(User $user, ClassModel $class): bool
    {
        // Only students can enroll
        if (! $user->isStudent()) {
            return false;
        }

        // Student cannot enroll if already enrolled
        if (Enrollment::where('student_id', $user->id)->where('class_id', $class->id)->where('status', '!=', 'dropped')->exists()) {
            return false;
        }

        // Class must be active
        if (! $class->is_active) {
            return false;
        }

        // Class must not be full
        if ($class->isFull()) {
            return false;
        }

        return true;
    }

    public function drop(User $user, ClassModel $class): bool
    {
        // Only students can drop their own enrollment
        if (! $user->isStudent()) {
            return false;
        }

        // Student must be enrolled in the class
        return Enrollment::where('student_id', $user->id)->where('class_id', $class->id)->where('status', '!=', 'dropped')->exists();
    }
}
