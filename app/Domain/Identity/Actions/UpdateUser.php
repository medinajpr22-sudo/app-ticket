<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    /**
     * Update a user's profile, access and team memberships.
     *
     * Leaving the password empty keeps the current one. Users who stop being
     * IT staff leave every team, because only staff can attend tickets.
     *
     * @param  array{
     *     role_id: int,
     *     name: string,
     *     email: string,
     *     department_id?: int|null,
     *     location_id?: int|null,
     *     job_title?: string|null,
     *     phone?: string|null,
     *     is_active: bool,
     *     password?: string|null,
     *     team_ids?: list<int>,
     * }  $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            $user->fill(Arr::except($attributes, ['team_ids', 'password']));

            if (filled($attributes['password'] ?? null)) {
                $user->password = $attributes['password'];
            }

            $user->save();

            $user->teams()->sync($user->refresh()->isStaff() ? ($attributes['team_ids'] ?? []) : []);

            return $user;
        });
    }
}
