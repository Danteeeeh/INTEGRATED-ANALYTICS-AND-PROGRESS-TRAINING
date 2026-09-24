<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('announcements.view');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if (! $user->hasPermission('announcements.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            if ($announcement->class_id) {
                return $user->classesInstructing()->where('id', $announcement->class_id)->exists();
            }

            return true;
        }

        if ($user->isStudent()) {
            if ($announcement->class_id) {
                return $user->enrolledClasses()->where('id', $announcement->class_id)->exists();
            }

            if ($announcement->course_id) {
                return $user->enrolledClasses()->where('course_id', $announcement->course_id)->exists();
            }

            if ($announcement->target_role_id) {
                return $user->role_id === $announcement->target_role_id;
            }

            return $announcement->audience_type === Announcement::AUDIENCE_INSTITUTION;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Admins and instructors may author announcements; instructors are
        // further scoped to the courses they manage via the controller.
        return $user->hasPermission('announcements.create') && ($user->isAdmin() || $user->isInstructor());
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if (! $user->hasPermission('announcements.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Instructor: only announcements they authored, or ones for their own class.
        if ($user->isInstructor()) {
            if ((int) $announcement->created_by === (int) $user->id) {
                return true;
            }

            return $announcement->class_id
                && $user->classesInstructing()->where('id', $announcement->class_id)->exists();
        }

        return false;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        if (! $user->hasPermission('announcements.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return (int) $announcement->created_by === (int) $user->id;
        }

        return false;
    }

    public function pin(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement) && $user->hasPermission('announcements.pin');
    }

    public function schedule(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement) && $user->hasPermission('announcements.schedule');
    }

    public function publish(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement) && $user->hasPermission('announcements.publish');
    }
}
