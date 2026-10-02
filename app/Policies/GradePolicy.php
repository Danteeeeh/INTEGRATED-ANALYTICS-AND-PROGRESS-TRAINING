<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if ($user->isAdmin() || $user->isInstructor() || $user->isStudent() || $user->isRegistrar()) {
            return true;
        }

        return $user->hasPermission('grades.view');
    }

    public function view(User $user, mixed $grade = null, array $context = []): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hasPermission('grades.view') && ! $user->isInstructor() && ! $user->isStudent()) {
            return false;
        }

        // Handle class-based authorization (for gradebook views)
        if (isset($context['class']) && $context['class'] instanceof ClassModel) {
            $class = $context['class'];
            if ($user->isInstructor()) {
                return $class->instructor_id === $user->id;
            }
            if ($user->isStudent()) {
                return $user->enrolledClasses()->where('classes.id', $class->id)->exists();
            }
        }

        if (! $grade instanceof Grade) {
            return $user->isInstructor() || $user->isStudent();
        }

        $classId = $grade->item?->class_id ?? $grade->item?->category?->class_id;

        if ($user->isInstructor()) {
            return $classId && $user->classesInstructing()->where('id', $classId)->exists();
        }

        if ($user->isStudent()) {
            return (int) $grade->student_id === (int) $user->id;
        }

        return false;
    }

    public function create(User $user, array $context = []): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Handle class-based authorization (for gradebook operations)
        if (isset($context['class']) && $context['class'] instanceof ClassModel) {
            $class = $context['class'];
            return $user->isInstructor() && $class->instructor_id === $user->id;
        }

        if ($user->isInstructor()) {
            return true;
        }

        return $user->hasPermission('grades.create');
    }

    public function update(User $user, mixed $grade = null, array $context = []): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hasPermission('grades.update') && ! $user->hasPermission('grades.release') && ! $user->isInstructor()) {
            return false;
        }

        // Handle class-based authorization (for gradebook operations)
        if (isset($context['class']) && $context['class'] instanceof ClassModel) {
            $class = $context['class'];
            return $user->isInstructor() && $class->instructor_id === $user->id;
        }

        if (! $grade instanceof Grade) {
            return $user->isInstructor();
        }

        $classId = $grade->item?->class_id ?? $grade->item?->category?->class_id;

        return $user->isInstructor() && $classId && $user->classesInstructing()->where('id', $classId)->exists();
    }

    public function delete(User $user, Grade $grade): bool
    {
        return $user->hasPermission('grades.delete') && $user->isAdmin();
    }
}
