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

    public function view(User $user, mixed $grade = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hasPermission('grades.view') && ! $user->isInstructor() && ! $user->isStudent()) {
            return false;
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

    public function create(User $user): bool
    {
        if ($user->isAdmin() || $user->isInstructor()) {
            return true;
        }

        return $user->hasPermission('grades.create');
    }

    public function update(User $user, mixed $grade = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hasPermission('grades.update') && ! $user->hasPermission('grades.release') && ! $user->isInstructor()) {
            return false;
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
