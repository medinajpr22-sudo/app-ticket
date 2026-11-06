<?php

namespace Tests\Feature\Http\Controllers\Management;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolePermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_without_role_management_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('management.roles.index'))
            ->assertForbidden();
    }

    public function test_reserved_permissions_never_apply_to_other_roles_even_if_stored(): void
    {
        Role::findBySlug(RoleSlug::Admin)->permissions()->sync(Permission::query()->pluck('id'));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('management.roles.index'))
            ->assertForbidden();
    }

    public function test_index_shows_every_role_with_its_permissions(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('management.roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/Roles/Index')
                ->has('roles', count(RoleSlug::cases()))
                ->where('roles.2.slug', RoleSlug::User->value)
                ->where('roles.2.permissions', [PermissionSlug::CreateTickets->value, PermissionSlug::ViewOwnTickets->value]));
    }

    public function test_update_replaces_the_permissions_of_each_delegable_role(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.roles.update'), [
                'permissions' => [
                    'admin' => [PermissionSlug::ViewTeamTickets->value, PermissionSlug::ManageCategories->value],
                    'user' => [PermissionSlug::CreateTickets->value],
                ],
            ])
            ->assertRedirect(route('management.roles.index'))
            ->assertSessionHas('success', 'Permisos actualizados.');

        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can(PermissionSlug::ManageCategories->value));
        $this->assertFalse($admin->can(PermissionSlug::TakeTickets->value));
        $this->assertFalse(User::factory()->create()->can(PermissionSlug::ViewOwnTickets->value));
    }

    public function test_update_accepts_the_same_permission_in_several_roles(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.roles.update'), [
                'permissions' => [
                    'admin' => [PermissionSlug::CreateTickets->value, PermissionSlug::ViewOwnTickets->value],
                    'user' => [PermissionSlug::CreateTickets->value, PermissionSlug::ViewOwnTickets->value],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('management.roles.index'));

        $this->assertTrue(User::factory()->admin()->create()->can(PermissionSlug::CreateTickets->value));
        $this->assertTrue(User::factory()->create()->can(PermissionSlug::CreateTickets->value));
    }

    public function test_update_rejects_permissions_reserved_for_the_super_admin(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.roles.update'), [
                'permissions' => [
                    'admin' => [PermissionSlug::ManageRoles->value],
                    'user' => [],
                ],
            ])
            ->assertSessionHasErrors([
                'permissions' => 'Solo el super administrador puede tener estos permisos: Gestionar roles y permisos.',
            ]);

        $this->assertFalse(User::factory()->admin()->create()->can(PermissionSlug::ManageRoles->value));
    }

    public function test_update_rejects_changes_to_the_super_admin_role(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.roles.update'), [
                'permissions' => [
                    'super_admin' => [],
                    'admin' => [],
                    'user' => [],
                ],
            ])
            ->assertSessionHasErrors(['permissions']);
    }
}
