<?php

namespace App\Policies;

use App\Models\TermsOfService;
use App\Models\User;

/**
 * Terms of service is administrative text, so writing it is admin-only.
 *
 * Everyone may read the currently active version — students are asked to accept
 * it — which is why `viewAny` is not gated behind a permission that only staff
 * hold.
 */
class TermsOfServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, TermsOfService $termsOfService): bool
    {
        return $user->isAdmin() || (bool) $termsOfService->is_active;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->hasPermission('terms_of_service.create');
    }

    public function update(User $user, TermsOfService $termsOfService): bool
    {
        return $user->isAdmin() && $user->hasPermission('terms_of_service.update');
    }

    public function delete(User $user, TermsOfService $termsOfService): bool
    {
        return $user->isAdmin() && $user->hasPermission('terms_of_service.delete');
    }

    /** Activating / deactivating is an update, not a separate capability. */
    public function activate(User $user, TermsOfService $termsOfService): bool
    {
        return $this->update($user, $termsOfService);
    }

    /** Any signed-in user may record their own acceptance. */
    public function accept(User $user, TermsOfService $termsOfService): bool
    {
        return (bool) $termsOfService->is_active;
    }
}