<?php

namespace App\Policies;

use App\Models\LearningPlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningPlanPolicy
{
    use HandlesAuthorization;

    public function view(User $user, LearningPlan $learningPlan): bool
    {
        // Student can only see their own plans.
        if ($user->isStudent()) {
            return $learningPlan->student_id === $user->id;
        }

        // Admin/registrar with permission can see any plan.
        if ($user->hasPermission('learning_plans.view')) {
            return true;
        }

        // Instructor can view plans of students in their classes.
        if ($user->isInstructor()) {
            return $learningPlan->student?->enrollments()
                ->whereHas('class', fn ($q) => $q->where('instructor_id', $user->id))
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('learning_plans.create') || $user->isStudent();
    }

    public function update(User $user, LearningPlan $learningPlan): bool
    {
        if ($user->isStudent()) {
            return $learningPlan->student_id === $user->id;
        }

        return $user->hasPermission('learning_plans.update');
    }
}