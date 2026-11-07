<?php

namespace App\Domain\Organization\Policies;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;

/**
 * What a team lead can do with their own team. Configuring teams in general
 * stays with the "teams.manage" permission; super admins pass through Gate::before.
 */
class TeamPolicy
{
    /**
     * Add assistants to the team and follow how each of them performs.
     */
    public function lead(User $user, Team $team): bool
    {
        return $user->hasPermission(PermissionSlug::LeadTeams) && $user->leadsTeam($team->id);
    }
}
