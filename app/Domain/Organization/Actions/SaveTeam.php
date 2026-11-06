<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Organization\Models\Team;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveTeam
{
    /**
     * Create or update a team together with its members and leads.
     *
     * @param  array{
     *     name: string,
     *     type: string,
     *     icon: string,
     *     description?: string|null,
     *     is_active: bool,
     *     members: list<array{user_id: int, is_lead: bool}>,
     * }  $attributes
     */
    public function handle(Team $team, array $attributes): Team
    {
        return DB::transaction(function () use ($team, $attributes): Team {
            $team->fill(Arr::except($attributes, ['members']))->save();

            $team->members()->sync(
                collect($attributes['members'])->mapWithKeys(fn (array $member): array => [
                    $member['user_id'] => ['is_lead' => $member['is_lead']],
                ]),
            );

            return $team;
        });
    }
}
