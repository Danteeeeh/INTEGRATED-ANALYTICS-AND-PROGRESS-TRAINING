<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('notifications.view');
    }

    public function view(User $user, Notification $notification): bool
    {
        if (! $user->hasPermission('notifications.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $notification->user_id === $user->id;
    }

    public function update(User $user, Notification $notification): bool
    {
        if (! $user->hasPermission('notifications.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $notification->user_id === $user->id;
    }

    public function preferences(User $user): bool
    {
        return $user->hasPermission('notifications.preferences');
    }
}
