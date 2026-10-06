<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sections.view');
    }

    public function view(User $user, Section $section): bool
    {
        return $user->hasPermission('sections.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('sections.create');
    }

    public function update(User $user, Section $section): bool
    {
        return $user->hasPermission('sections.update');
    }

    /**
     * A section still holding classes or students cannot be removed.
     */
    public function delete(User $user, Section $section): bool
    {
        if (! $user->hasPermission('sections.delete')) {
            return false;
        }

        return ! $section->classes()->exists() && ! $section->students()->exists();
    }
}