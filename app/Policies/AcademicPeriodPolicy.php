<?php

namespace App\Policies;

use App\Models\AcademicPeriod;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcademicPeriodPolicy
{
    use HandlesAuthorization;

    public function view(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $user->hasPermission('academic_periods.view');
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('academic_periods.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('academic_periods.create');
    }

    public function update(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $user->hasPermission('academic_periods.update');
    }

    public function delete(User $user, AcademicPeriod $academicPeriod): bool
    {
        if (! $user->hasPermission('academic_periods.delete')) {
            return false;
        }

        // Prevent deletion if period has active courses/classes
        if ($academicPeriod->courses()->exists() || $academicPeriod->classes()->exists()) {
            return false;
        }

        return true;
    }
}
