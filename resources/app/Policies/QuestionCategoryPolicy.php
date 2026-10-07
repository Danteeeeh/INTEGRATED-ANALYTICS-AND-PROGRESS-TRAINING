<?php

namespace App\Policies;

use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization for Test Bank categories (§7, §16).
 *
 * Categories are authoring metadata, so they inherit the Test Bank's own
 * permission set rather than inventing new ones that the seeder would then have
 * to re-issue. Students hold none of these permissions and have no route to the
 * controller regardless.
 */
class QuestionCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('question_banks.view');
    }

    public function view(User $user, QuestionCategory $category): bool
    {
        if (! $user->hasPermission('question_banks.view')) {
            return false;
        }

        if ($user->isStudent()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // A null course_id means the category is global, which anyone may read.
        if ($category->course_id === null) {
            return true;
        }

        return $category->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return ! $user->isStudent()
            && $user->hasPermission('question_banks.create');
    }

    public function update(User $user, QuestionCategory $category): bool
    {
        if ($user->isStudent() || ! $user->hasPermission('question_banks.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $category->created_by === $user->id;
    }

    public function delete(User $user, QuestionCategory $category): bool
    {
        if ($user->isStudent() || ! $user->hasPermission('question_banks.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $category->created_by === $user->id;
    }
}
