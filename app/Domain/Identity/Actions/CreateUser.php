<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    /**
     * Create a user and, when they are IT staff, add them to their teams.
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
    public function handle(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = User::query()->create(Arr::except($attributes, ['team_ids']));

            if ($user->isStaff()) {
                $user->teams()->sync($attributes['team_ids'] ?? []);
            }

            return $user;
        });
    }
}
