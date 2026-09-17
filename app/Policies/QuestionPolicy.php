<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuestionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('questions.view');
    }

    public function view(User $user, Question $question): bool
    {
        if (! $user->hasPermission('questions.view')) {
            return false;
        }

        $bank = $question->bank;

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return true;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('course_id', $bank->course_id)->exists()
                || $user->enrolledClasses()->where('classes.id', $bank->class_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('questions.create')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return true;
        }

        return false;
    }

    public function update(User $user, Question $question): bool
    {
        if (! $user->hasPermission('questions.update')) {
            return false;
        }

        $bank = $question->bank;

        if ($user->isAdmin()) {
            return true;
        }

        return $bank->created_by === $user->id;
    }

    public function delete(User $user, Question $question): bool
    {
        if (! $user->hasPermission('questions.delete')) {
            return false;
        }

        $bank = $question->bank;

        if ($user->isAdmin()) {
            return true;
        }

        return $bank->created_by === $user->id;
    }

    public function reuse(User $user, Question $question): bool
    {
        if (! $user->hasPermission('questions.reuse')) {
            return false;
        }

        $viewGranted = $this->view($user, $question);

        if (! $viewGranted) {
            return false;
        }

        return $user->hasPermission('questions.create');
    }
}
