<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPreferencePolicy
{
    use HandlesAuthorization;

    public function view(User $user, ?UserPreference $preference = null): bool
    {
        if ($user->hasPermission('notifications.preferences')) {
            return true;
        }

        return $preference !== null && $user->id === $preference->user_id;
    }

    public function update(User $user, ?UserPreference $preference = null): bool
    {
        if ($user->hasPermission('notifications.preferences')) {
            return true;
        }

        return $preference !== null && $user->id === $preference->user_id;
    }
}
