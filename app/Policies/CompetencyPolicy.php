<?php

namespace App\Policies;

use App\Models\Competency;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompetencyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('competencies.view');
    }

    public function view(User $user, Competency $competency): bool
    {
        if (! $user->hasPermission('competencies.view')) {
            return false;
        }

        return $user->isAdmin() || $user->isInstructor() || $user->isStudent();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('competencies.create');
    }

    public function update(User $user, Competency $competency): bool
    {
        return $user->hasPermission('competencies.update');
    }

    public function delete(User $user, Competency $competency): bool
    {
        return $user->hasPermission('competencies.delete');
    }

    public function evidence(User $user, Competency $competency): bool
    {
        return $user->hasPermission('competencies.evidence');
    }
}
