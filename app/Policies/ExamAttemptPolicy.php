<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamAttemptPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('exam_attempts.view');
    }

    public function view(User $user, ExamAttempt $attempt): bool
    {
        if (! $user->hasPermission('exam_attempts.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $attempt->exam->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $attempt->student_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('exam_attempts.create')) {
            return false;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->exists();
        }

        return false;
    }

    public function delete(User $user, ExamAttempt $attempt): bool
    {
        if (! $user->hasPermission('exam_attempts.delete')) {
            return false;
        }

        return $user->isAdmin();
    }

    public function grade(User $user, ExamAttempt $attempt): bool
    {
        if (! $user->hasPermission('exam_attempts.grade')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $attempt->exam->class_id)->exists();
        }

        return false;
    }

    public function review(User $user, ExamAttempt $attempt): bool
    {
        return $this->view($user, $attempt);
    }
}
