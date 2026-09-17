<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quizzes.view');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        if (! $user->hasPermission('quizzes.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $quiz->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('classes.id', $quiz->class_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('quizzes.create')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->exists();
        }

        return false;
    }

    public function update(User $user, Quiz $quiz): bool
    {
        if (! $user->hasPermission('quizzes.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $quiz->class_id)->exists();
        }

        return false;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        if (! $user->hasPermission('quizzes.delete')) {
            return false;
        }

        return $user->isAdmin();
    }

    public function grade(User $user, Quiz $quiz): bool
    {
        if (! $user->hasPermission('quizzes.grade')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $quiz->class_id)->exists();
        }

        return false;
    }

    public function attempt(User $user, Quiz $quiz): bool
    {
        if (! $user->hasPermission('quizzes.attempt')) {
            return false;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('classes.id', $quiz->class_id)->exists();
        }

        return false;
    }

    public function publish(User $user, Quiz $quiz): bool
    {
        if (! $user->hasPermission('quizzes.publish')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $quiz->class_id)->exists();
        }

        return false;
    }

    public function close(User $user, Quiz $quiz): bool
    {
        if (! $user->hasPermission('quizzes.close')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $quiz->class_id)->exists();
        }

        return false;
    }
}
