<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('departments.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->hasPermission('departments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('departments.create');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->hasPermission('departments.update');
    }

    /**
     * A department still holding programs, courses or students cannot be
     * removed — deleting it would orphan all of them and silently drop the
     * department filter from every listing.
     */
    public function delete(User $user, Department $department): bool
    {
        if (! $user->hasPermission('departments.delete')) {
            return false;
        }

        // Students hang off a department through their program.
        $hasStudents = $department->programs()
            ->whereHas('students')
            ->exists();

        return ! $department->programs()->exists()
            && ! $department->courses()->exists()
            && ! $hasStudents;
    }
}