<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use Illuminate\Support\Facades\DB;

class SyncRolePermissions
{
    /**
     * Replace the permissions of every delegable role. The super admin always
     * keeps every permission and is never changed here.
     *
     * @param  array<string, list<string>>  $permissionsByRole  Permission slugs keyed by role slug.
     */
    public function handle(array $permissionsByRole): void
    {
        DB::transaction(function () use ($permissionsByRole): void {
            foreach ($permissionsByRole as $roleSlug => $permissionSlugs) {
                $role = RoleSlug::from($roleSlug);

                if (! $role->isDelegable()) {
                    continue;
                }

                $delegable = array_filter(
                    array_map(PermissionSlug::from(...), $permissionSlugs),
                    fn (PermissionSlug $permission): bool => ! $permission->isReservedForSuperAdmin(),
                );

                Role::findBySlug($role)->permissions()->sync(
                    Permission::query()->whereIn('slug', $delegable)->pluck('id'),
                );
            }
        });
    }
}
