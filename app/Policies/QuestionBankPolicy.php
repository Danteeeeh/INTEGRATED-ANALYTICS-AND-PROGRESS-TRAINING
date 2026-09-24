<?php

namespace App\Policies;

use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuestionBankPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('question_banks.view');
    }

    public function view(User $user, QuestionBank $bank): bool
    {
        if (! $user->hasPermission('question_banks.view')) {
            return false;
        }

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
        if (! $user->hasPermission('question_banks.create')) {
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

    public function update(User $user, QuestionBank $bank): bool
    {
        if (! $user->hasPermission('question_banks.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $bank->created_by === $user->id;
    }

    public function delete(User $user, QuestionBank $bank): bool
    {
        if (! $user->hasPermission('question_banks.delete')) {
            return false;
        }

        return $user->isAdmin();
    }

    public function import(User $user): bool
    {
        if (! $user->hasPermission('question_banks.import')) {
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

    public function export(User $user, QuestionBank $bank): bool
    {
        if (! $user->hasPermission('question_banks.export')) {
            return false;
        }

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
}
