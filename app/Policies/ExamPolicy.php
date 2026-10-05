<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamPolicy
{
    use HandlesAuthorization;

    protected function instructorManagesExam(User $user, Exam $exam): bool
    {
        if ($exam->class_id) {
            return $user->classesInstructing()->where('id', $exam->class_id)->exists();
        }

        if ($exam->course_id) {
            return $user->classesInstructing()->where('course_id', $exam->course_id)->exists();
        }

        return (int) $exam->created_by === (int) $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('exams.view');
    }

    public function view(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $this->instructorManagesExam($user, $exam);
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('classes.id', $exam->class_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermission('exams.create')) {
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

    public function update(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $this->instructorManagesExam($user, $exam);
        }

        return false;
    }

    public function delete(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $this->instructorManagesExam($user, $exam);
        }

        return false;
    }

    public function grade(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.grade')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->classesInstructing()->where('id', $exam->class_id)->exists();
        }

        return false;
    }

    public function attempt(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.attempt')) {
            return false;
        }

        if ($user->isStudent()) {
            return $user->enrolledClasses()->where('classes.id', $exam->class_id)->exists();
        }

        return false;
    }

    public function publish(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.publish')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $this->instructorManagesExam($user, $exam);
        }

        return false;
    }

    public function close(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.close')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $this->instructorManagesExam($user, $exam);
        }

        return false;
    }

    public function reopen(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.reopen')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $this->instructorManagesExam($user, $exam);
        }

        return false;
    }

    public function extendDeadline(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.extend_deadline')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $this->instructorManagesExam($user, $exam);
        }

        return false;
    }

    public function resetAttempt(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.reset_attempt')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $this->instructorManagesExam($user, $exam);
        }

        return false;
    }

    public function manageSettings(User $user, Exam $exam): bool
    {
        if (! $user->hasPermission('exams.manage_settings')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $this->instructorManagesExam($user, $exam);
        }

        return false;
    }
}
