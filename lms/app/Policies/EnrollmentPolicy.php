<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Enrollment $enrollment): bool
    {
        if (! $user->hasPermission('enrollments.view') && ! $user->isStudent()) {
            return false;
        }

        if ($user->isAdmin() && $user->hasPermission('enrollments.view')) {
            return true;
        }

        if ($user->isInstructor()) {
            return $enrollment->class?->instructor_id === $user->id;
        }

        if ($user->isStudent()) {
            return $enrollment->student_id === $user->id;
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('enrollments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('enrollments.create');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        if (! $user->hasPermission('enrollments.update')) {
            return false;
        }

        // Admin can update any enrollment
        if ($user->isAdmin()) {
            return true;
        }

        // Instructor can update enrollments in their classes
        if ($user->isInstructor()) {
            return $enrollment->class->instructor_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        if (! $user->hasPermission('enrollments.delete')) {
            return false;
        }

        // Admin can delete any enrollment
        if ($user->isAdmin()) {
            return true;
        }

        // Instructor can delete enrollments in their classes
        if ($user->isInstructor()) {
            return $enrollment->class->instructor_id === $user->id;
        }

        return false;
    }

    public function drop(User $user, Enrollment $enrollment): bool
    {
        // Admin can drop any student
        if ($user->isAdmin()) {
            return true;
        }

        // Instructor can drop students from their classes
        if ($user->isInstructor()) {
            return $enrollment->class->instructor_id === $user->id;
        }

        if ($user->isStudent()) {
            return $enrollment->student_id === $user->id;
        }

        return false;
    }

    public function grade(User $user, Enrollment $enrollment): bool
    {
        if (! $user->hasPermission('grades.update')) {
            return false;
        }

        // Admin can grade any enrollment
        if ($user->isAdmin()) {
            return true;
        }

        // Instructor can grade students in their classes
        if ($user->isInstructor()) {
            return $enrollment->class->instructor_id === $user->id;
        }

        return false;
    }
}
