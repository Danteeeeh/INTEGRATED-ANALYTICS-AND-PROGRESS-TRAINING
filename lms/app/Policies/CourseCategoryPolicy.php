<?php

namespace App\Policies;

use App\Models\CourseCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CourseCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('course_categories.view');
    }

    public function view(User $user, CourseCategory $courseCategory): bool
    {
        return $user->hasPermission('course_categories.view');
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('course_categories.create')) {
            return false;
        }

        return $user->isAdmin();
    }

    public function update(User $user, CourseCategory $courseCategory): bool
    {
        if (! $user->hasPermission('course_categories.update')) {
            return false;
        }

        return $user->isAdmin();
    }

    public function delete(User $user, CourseCategory $courseCategory): bool
    {
        if (! $user->hasPermission('course_categories.delete')) {
            return false;
        }

        return $user->isAdmin();
    }
}
