<?php

namespace App\Policies;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaFilePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('media.view');
    }

    public function view(User $user, MediaFile $mediaFile): bool
    {
        if (! $user->hasPermission('media.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($mediaFile->uploader_id === $user->id) {
            return true;
        }

        if ($user->isInstructor()) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('media.create');
    }

    public function update(User $user, MediaFile $mediaFile): bool
    {
        if (! $user->hasPermission('media.update')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $mediaFile->uploader_id === $user->id;
    }

    public function delete(User $user, MediaFile $mediaFile): bool
    {
        if (! $user->hasPermission('media.delete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $mediaFile->uploader_id === $user->id;
    }

    public function download(User $user, MediaFile $mediaFile): bool
    {
        if (! $user->hasPermission('media.download')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($mediaFile->uploader_id === $user->id) {
            return true;
        }

        if ($user->isInstructor()) {
            return true;
        }

        return false;
    }
}
