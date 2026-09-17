<?php

namespace App\Policies;

use App\Models\AssignmentSubmission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentSubmissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('assignment_submissions.view');
    }

    public function view(User $user, AssignmentSubmission $submission): bool
    {
        if (! $user->hasPermission('assignment_submissions.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $submission->assignment->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $submission->student_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('assignment_submissions.create')) {
            return false;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->exists();
        }

        return false;
    }

    public function update(User $user, AssignmentSubmission $submission): bool
    {
        if (! $user->hasPermission('assignment_submissions.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $submission->assignment->class_id)->exists();
        }

        if ($user->isStudent()) {
            return $submission->student_id === $user->id && $submission->status === 'in_progress';
        }

        return false;
    }

    public function delete(User $user, AssignmentSubmission $submission): bool
    {
        if (! $user->hasPermission('assignment_submissions.delete')) {
            return false;
        }

        return $user->isAdmin();
    }

    public function grade(User $user, AssignmentSubmission $submission): bool
    {
        if (! $user->hasPermission('assignment_submissions.grade')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $submission->assignment->class_id)->exists();
        }

        return false;
    }

    public function history(User $user, AssignmentSubmission $submission): bool
    {
        return $this->view($user, $submission);
    }
}
