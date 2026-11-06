<?php

namespace App\Http\Controllers\Management;

use App\Domain\Identity\Actions\SyncRolePermissions;
use App\Domain\Identity\Enums\PermissionGroup;
use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\UpdateRolePermissionsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    /**
     * Display what each role can do.
     */
    public function index(): Response
    {
        $roles = Role::query()->with('permissions')->withCount('users')->orderBy('id')->get();

        return Inertia::render('Management/Roles/Index', [
            'roles' => $roles->map(fn (Role $role): array => [
                'slug' => $role->slug->value,
                'label' => $role->slug->label(),
                'description' => $role->slug->description(),
                'users_count' => $role->users_count,
                'is_locked' => ! $role->slug->isDelegable(),
                'permissions' => $role->slug->isDelegable()
                    ? $role->permissions->map(fn (Permission $permission): string => $permission->slug->value)->values()->all()
                    : array_map(fn (PermissionSlug $permission): string => $permission->value, PermissionSlug::cases()),
            ])->all(),
            'groups' => array_map(fn (PermissionGroup $group): array => [
                'value' => $group->value,
                'label' => $group->label(),
                'permissions' => array_values(array_map(fn (PermissionSlug $permission): array => [
                    'slug' => $permission->value,
                    'label' => $permission->label(),
                    'is_reserved' => $permission->isReservedForSuperAdmin(),
                ], array_filter(PermissionSlug::cases(), fn (PermissionSlug $permission): bool => $permission->group() === $group))),
            ], PermissionGroup::cases()),
        ]);
    }

    /**
     * Save the permissions of the delegable roles.
     */
    public function update(UpdateRolePermissionsRequest $request, SyncRolePermissions $syncPermissions): RedirectResponse
    {
        $syncPermissions->handle($request->validated('permissions'));

        return to_route('management.roles.index')->with('success', __('Permissions updated.'));
    }
}
