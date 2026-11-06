<?php

namespace App\Domain\Identity\Policies;

use App\Domain\Identity\Models\User;

class UserPolicy
{
    /**
     * Someone who was delegated user management cannot touch a super admin.
     * Super admins pass every check through Gate::before.
     */
    public function update(User $actor, User $user): bool
    {
        return ! $user->isSuperAdmin();
    }
}
