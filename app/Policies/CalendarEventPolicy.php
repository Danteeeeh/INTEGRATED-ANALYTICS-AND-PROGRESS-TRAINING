<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CalendarEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('calendar.view');
    }

    public function view(User $user, CalendarEvent $event): bool
    {
        if (! $user->hasPermission('calendar.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($event->user_id === $user->id) {
            return true;
        }

        if ($user->isInstructor()) {
            if ($event->class_id) {
                return $user->classesInstructing()->where('id', $event->class_id)->exists();
            }

            if ($event->course_id) {
                return $user->classesInstructing()->where('course_id', $event->course_id)->exists();
            }
        }

        if ($user->isStudent()) {
            if ($event->class_id) {
                return $user->enrolledClasses()->where('id', $event->class_id)->exists();
            }

            if ($event->course_id) {
                return $user->enrolledClasses()->where('course_id', $event->course_id)->exists();
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('calendar.create') && ($user->isAdmin() || $user->isInstructor() || $user->isStudent());
    }

    public function update(User $user, CalendarEvent $event): bool
    {
        if (! $user->hasPermission('calendar.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($event->user_id === $user->id) {
            return true;
        }

        if ($user->isInstructor()) {
            if ($event->class_id) {
                return $user->classesInstructing()->where('id', $event->class_id)->exists();
            }

            if ($event->course_id) {
                return $user->classesInstructing()->where('course_id', $event->course_id)->exists();
            }
        }

        return false;
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        if (! $user->hasPermission('calendar.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($event->user_id === $user->id) {
            return true;
        }

        if ($user->isInstructor()) {
            if ($event->class_id) {
                return $user->classesInstructing()->where('id', $event->class_id)->exists();
            }

            if ($event->course_id) {
                return $user->classesInstructing()->where('course_id', $event->course_id)->exists();
            }
        }

        return false;
    }
}
