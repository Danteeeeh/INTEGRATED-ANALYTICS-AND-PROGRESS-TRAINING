<?php

namespace App\Policies;

use App\Models\GradeCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradeCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('grade_categories.view');
    }

    public function view(User $user, GradeCategory $cat): bool
    {
        if (! $user->hasPermission('grade_categories.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $cat->class_id && $user->classesInstructing()->where('id', $cat->class_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('grade_categories.create')) {
            return false;
        }

        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, GradeCategory $cat): bool
    {
        if (! $user->hasPermission('grade_categories.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $cat->class_id && $user->classesInstructing()->where('id', $cat->class_id)->exists();
        }

        return false;
    }

    public function delete(User $user, GradeCategory $cat): bool
    {
        return $user->hasPermission('grade_categories.delete') && $user->isAdmin();
    }
}
