<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizAttemptPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quiz_attempts.view');
    }

    public function view(User $user, QuizAttempt $attempt): bool
    {
        if (! $user->hasPermission('quiz_attempts.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $attempt->quiz->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $attempt->student_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('quiz_attempts.create')) {
            return false;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->exists();
        }

        return false;
    }

    public function delete(User $user, QuizAttempt $attempt): bool
    {
        if (! $user->hasPermission('quiz_attempts.delete')) {
            return false;
        }

        return $user->isAdmin();
    }

    public function grade(User $user, QuizAttempt $attempt): bool
    {
        if (! $user->hasPermission('quiz_attempts.grade')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $attempt->quiz->class_id)->exists();
        }

        return false;
    }

    public function review(User $user, QuizAttempt $attempt): bool
    {
        return $this->view($user, $attempt);
    }
}
