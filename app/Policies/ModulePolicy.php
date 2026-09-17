<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ModulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('modules.view');
    }

    public function view(User $user, Module $module): bool
    {
        if (! $user->hasPermission('modules.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $module->course_id)->exists();
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('course_id', $module->course_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('modules.create')) {
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

    public function update(User $user, Module $module): bool
    {
        if (! $user->hasPermission('modules.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $module->course_id)->exists();
        }

        return false;
    }

    public function delete(User $user, Module $module): bool
    {
        if (! $user->hasPermission('modules.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $module->course_id)->exists();
        }

        return false;
    }

    public function publish(User $user, Module $module): bool
    {
        if (! $user->hasPermission('modules.publish')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $module->course_id)->exists();
        }

        return false;
    }

    public function reorder(User $user, Module $module): bool
    {
        if (! $user->hasPermission('modules.reorder')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('course_id', $module->course_id)->exists();
        }

        return false;
    }
}
