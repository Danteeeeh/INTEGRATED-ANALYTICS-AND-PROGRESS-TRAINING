<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Auth\Access\HandlesAuthorization;

class VirtualClassPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('virtual_classes.view');
    }

    public function view(User $user, VirtualClass $vc): bool
    {
        if (! $user->hasPermission('virtual_classes.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            if ($vc->instructor_id === $user->id) {
                return true;
            }

            return $vc->class_id && $user->classesInstructing()->where('id', $vc->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $vc->class_id && $user->enrolledClasses()->where('id', $vc->class_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('virtual_classes.create')) {
            return false;
        }

        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, VirtualClass $vc): bool
    {
        if (! $user->hasPermission('virtual_classes.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            if ($vc->instructor_id === $user->id) {
                return true;
            }

            return $vc->class_id && $user->classesInstructing()->where('id', $vc->class_id)->exists();
        }

        return false;
    }

    public function delete(User $user, VirtualClass $vc): bool
    {
        return $user->hasPermission('virtual_classes.delete') && $user->isAdmin();
    }

    public function join(User $user, VirtualClass $vc): bool
    {
        if (! $user->hasPermission('virtual_classes.join')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            if ($vc->instructor_id === $user->id) {
                return true;
            }

            return $vc->class_id && $user->classesInstructing()->where('id', $vc->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $vc->class_id && $user->enrolledClasses()->where('id', $vc->class_id)->exists();
        }

        return false;
    }

    public function start(User $user, VirtualClass $vc): bool
    {
        if (! $user->hasPermission('virtual_classes.start')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $vc->instructor_id === $user->id;
    }
}
