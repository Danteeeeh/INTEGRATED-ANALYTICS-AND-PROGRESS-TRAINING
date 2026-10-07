<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProgramPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('programs.view');
    }

    public function view(User $user, Program $program): bool
    {
        return $user->hasPermission('programs.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('programs.create');
    }

    public function update(User $user, Program $program): bool
    {
        return $user->hasPermission('programs.update');
    }

    /**
     * A program still holding sections, courses or students cannot be removed.
     */
    public function delete(User $user, Program $program): bool
    {
        if (! $user->hasPermission('programs.delete')) {
            return false;
        }

        return ! $program->sections()->exists()
            && ! $program->courses()->exists()
            && ! $program->students()->exists();
    }
}