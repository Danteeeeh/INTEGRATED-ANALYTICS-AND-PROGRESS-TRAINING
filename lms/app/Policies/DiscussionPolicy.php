<?php

namespace App\Policies;

use App\Models\Discussion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscussionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('discussions.view');
    }

    public function view(User $user, Discussion $discussion): bool
    {
        if (! $user->hasPermission('discussions.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $discussion->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('id', $discussion->class_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('discussions.create') && ($user->isAdmin() || $user->isInstructor() || $user->isStudent());
    }

    public function update(User $user, Discussion $discussion): bool
    {
        if (! $user->hasPermission('discussions.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $discussion->class_id)->exists();
        }

        return $discussion->created_by === $user->id;
    }

    public function delete(User $user, Discussion $discussion): bool
    {
        if (! $user->hasPermission('discussions.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $discussion->class_id)->exists();
        }

        return false;
    }

    public function pin(User $user, Discussion $discussion): bool
    {
        if (! $user->hasPermission('discussions.pin')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $discussion->class_id)->exists();
        }

        return false;
    }

    public function lock(User $user, Discussion $discussion): bool
    {
        if (! $user->hasPermission('discussions.lock')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $discussion->class_id)->exists();
        }

        return false;
    }

    public function moderate(User $user, Discussion $discussion): bool
    {
        if (! $user->hasPermission('discussions.moderate')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $discussion->class_id)->exists();
        }

        return false;
    }

    public function subscribe(User $user, Discussion $discussion): bool
    {
        if (! $user->hasPermission('discussions.subscribe')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $discussion->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('id', $discussion->class_id)->exists();
        }

        return false;
    }
}
