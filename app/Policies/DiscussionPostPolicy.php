<?php

namespace App\Policies;

use App\Models\DiscussionPost;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscussionPostPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('discussion_posts.view');
    }

    public function view(User $user, DiscussionPost $post): bool
    {
        if (! $user->hasPermission('discussion_posts.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $classId = $post->discussion?->class_id;

        if ($user->isInstructor()) {
            return $classId && $user->classesInstructing()->where('id', $classId)->exists();
        }

        if ($user->isStudent()) {
            return $classId && $user->enrolledClasses()->where('id', $classId)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('discussion_posts.create') && ($user->isAdmin() || $user->isInstructor() || $user->isStudent());
    }

    public function update(User $user, DiscussionPost $post): bool
    {
        if (! $user->hasPermission('discussion_posts.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $classId = $post->discussion?->class_id;

        if ($user->isInstructor()) {
            return ($classId && $user->classesInstructing()->where('id', $classId)->exists())
                || $post->author_id === $user->id;
        }

        return $post->author_id === $user->id;
    }

    public function delete(User $user, DiscussionPost $post): bool
    {
        if (! $user->hasPermission('discussion_posts.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $classId = $post->discussion?->class_id;

        if ($user->isInstructor()) {
            return ($classId && $user->classesInstructing()->where('id', $classId)->exists())
                || $post->author_id === $user->id;
        }

        return $post->author_id === $user->id;
    }

    public function reply(User $user, DiscussionPost $post): bool
    {
        return $this->create($user);
    }

    public function report(User $user, DiscussionPost $post): bool
    {
        return $this->view($user, $post);
    }
}
