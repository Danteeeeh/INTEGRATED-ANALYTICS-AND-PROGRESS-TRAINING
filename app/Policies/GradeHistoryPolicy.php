<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradeHistoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('grade_history.view') || $user->hasPermission('grades.history');
    }

    public function view(User $user, mixed $history = null): bool
    {
        return $this->viewAny($user);
    }
}
