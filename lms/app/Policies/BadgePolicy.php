<?php

namespace App\Policies;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BadgePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('badges.view');
    }

    public function view(User $user, Badge $badge): bool
    {
        if (! $user->hasPermission('badges.view')) {
            return false;
        }

        return $user->isAdmin() || $user->isInstructor() || $user->isStudent();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('badges.create');
    }

    public function update(User $user, Badge $badge): bool
    {
        return $user->hasPermission('badges.update');
    }

    public function delete(User $user, Badge $badge): bool
    {
        return $user->hasPermission('badges.delete');
    }

    public function award(User $user, Badge $badge): bool
    {
        return $user->hasPermission('badges.award');
    }
}
