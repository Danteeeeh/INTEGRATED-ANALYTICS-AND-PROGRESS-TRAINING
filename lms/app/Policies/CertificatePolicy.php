<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('certificates.view');
    }

    public function view(User $user, Certificate $certificate): bool
    {
        if (! $user->hasPermission('certificates.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $certificate->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('certificates.create');
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->hasPermission('certificates.update');
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->hasPermission('certificates.delete');
    }

    public function issue(User $user, Certificate $certificate): bool
    {
        return $user->hasPermission('certificates.issue');
    }

    public function download(User $user, Certificate $certificate): bool
    {
        return $user->hasPermission('certificates.download')
            && ($user->isAdmin() || $certificate->student_id === $user->id);
    }
}
