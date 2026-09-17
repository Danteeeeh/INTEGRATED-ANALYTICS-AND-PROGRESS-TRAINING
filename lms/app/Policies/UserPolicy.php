<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view')
            || $user->hasPermission('students.view')
            || $user->hasPermission('instructors.view');
    }

    public function view(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }

        return $this->canManageTarget($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create')
            || $user->hasPermission('students.create')
            || $user->hasPermission('instructors.create');
    }

    public function update(User $user, User $target): bool
    {
        return $this->canManageTarget($user, $target, 'update');
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $this->canManageTarget($user, $target, 'delete');
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $this->canManageTarget($user, $target, 'update');
    }

    public function approve(User $user, User $target): bool
    {
        return $this->canManageTarget($user, $target, 'update');
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('users.import') || $user->hasPermission('users.create');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('users.export') || $user->hasPermission('users.view');
    }

    protected function canManageTarget(User $user, User $target, string $action = 'view'): bool
    {
        if ($user->hasPermission("users.{$action}") || $user->hasPermission('users.view') && $action === 'view') {
            return true;
        }

        if ($target->isStudent() && $user->hasPermission("students.{$action}")) {
            return true;
        }

        if ($target->isInstructor() && $user->hasPermission("instructors.{$action}")) {
            return true;
        }

        return false;
    }
}
