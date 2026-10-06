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

        // The Test Bank is authoring tooling; students have no route to it and
        // no ability to see a bank through a policy either (§17).
        if ($user->isStudent()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isInstructor()) {
            return false;
        }

        // Own banks plus anything an admin flagged as shared.
        return $bank->created_by === $user->id || (bool) $bank->is_shared;
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

        if ($user->isAdmin()) {
            return true;
        }

        // The controller still refuses while the bank holds questions, so this
        // cannot cascade questions away from an assessment (§5).
        return $user->isInstructor() && $bank->created_by === $user->id;
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

        if ($user->isStudent()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $bank->created_by === $user->id || (bool) $bank->is_shared;
        }

        return false;
    }
}
