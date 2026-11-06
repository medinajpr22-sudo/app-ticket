<?php

namespace Tests\Feature\Database\Seeders;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{RoleSlug}>
     */
    public static function roles(): array
    {
        return [
            'super admin' => [RoleSlug::SuperAdmin],
            'admin' => [RoleSlug::Admin],
            'user' => [RoleSlug::User],
        ];
    }

    #[DataProvider('roles')]
    public function test_each_role_is_granted_exactly_its_default_permissions(RoleSlug $roleSlug): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['role_id' => Role::findBySlug($roleSlug)->id]);
        $expected = PermissionSlug::defaultsFor($roleSlug);

        foreach (PermissionSlug::cases() as $permission) {
            $this->assertSame(
                in_array($permission, $expected, true),
                Gate::forUser($user)->allows($permission->value),
                "{$roleSlug->value} → {$permission->value}",
            );
        }
    }

    public function test_running_again_keeps_permissions_granted_afterwards(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $userRole = Role::findBySlug(RoleSlug::User);
        $userRole->permissions()->attach(Permission::query()->where('slug', PermissionSlug::ViewReports)->firstOrFail());

        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseCount('roles', count(RoleSlug::cases()));
        $this->assertTrue($userRole->permissions()->where('slug', PermissionSlug::ViewReports)->exists());
    }
}
