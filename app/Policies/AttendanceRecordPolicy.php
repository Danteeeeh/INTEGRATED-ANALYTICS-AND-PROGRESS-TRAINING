<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view');
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        if (! $user->hasPermission('attendance.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $record->class_id && $user->classesInstructing()->where('id', $record->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $record->student_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('attendance.create')) {
            return false;
        }

        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, AttendanceRecord $record): bool
    {
        if (! $user->hasPermission('attendance.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $record->class_id && $user->classesInstructing()->where('id', $record->class_id)->exists();
        }

        return false;
    }

    public function delete(User $user, AttendanceRecord $record): bool
    {
        return $user->hasPermission('attendance.delete') && $user->isAdmin();
    }

    public function mark(User $user, AttendanceRecord $record): bool
    {
        return $this->update($user, $record);
    }

    public function export(User $user): bool
    {
        if (! $user->hasPermission('attendance.export')) {
            return false;
        }

        return $user->isAdmin() || $user->isInstructor();
    }

    public function reports(User $user): bool
    {
        return $this->export($user);
    }
}
