<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Reference data required in every environment. Safe to run repeatedly:
 * it never removes permissions a super admin granted afterwards.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PermissionSlug::cases() as $permission) {
            Permission::query()->firstOrCreate(['slug' => $permission]);
        }

        foreach (RoleSlug::cases() as $roleSlug) {
            $role = Role::query()->firstOrCreate(['slug' => $roleSlug]);

            $role->permissions()->syncWithoutDetaching(
                Permission::query()
                    ->whereIn('slug', PermissionSlug::defaultsFor($roleSlug))
                    ->pluck('id'),
            );
        }
    }
}
