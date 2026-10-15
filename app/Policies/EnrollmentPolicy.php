<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Enrollment $enrollment): bool
    {
        if (! $user->hasPermission('enrollments.view') && ! $user->isStudent()) {
            return false;
        }

        if ($user->isAdmin() && $user->hasPermission('enrollments.view')) {
            return true;
        }

        if ($user->isInstructor()) {
            return $enrollment->class?->instructor_id === $user->id;
        }

        if ($user->isStudent()) {
            return $enrollment->student_id === $user->id;
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('enrollments.view');
    }

    // create/update/delete/drop/grade were removed: enrollment and official
    // grades are authoritative in AA and arrive via "Sync from AA" only.
    // The policy intentionally keeps view/viewAny so read-only panels work.
}
