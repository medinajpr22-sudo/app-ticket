<?php

namespace Tests\Feature\Http\Controllers\Management;

use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('management.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_without_user_management_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('management.users.index'))
            ->assertForbidden();
    }

    public function test_index_filters_users_by_search_role_and_status(): void
    {
        User::factory()->admin()->create(['name' => 'Ana Torres']);
        User::factory()->create(['name' => 'Ana Ruiz']);
        User::factory()->inactive()->create(['name' => 'Ana Gómez']);
        User::factory()->create(['name' => 'Jorge Peña']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('management.users.index', ['search' => 'Ana', 'role' => 'user', 'status' => 'active']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Ana Ruiz'));
    }

    public function test_store_creates_staff_user_with_teams_and_without_password(): void
    {
        $team = Team::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.users.store'), $this->payload([
                'role_id' => Role::findBySlug(RoleSlug::Admin)->id,
                'team_ids' => [$team->id],
            ]))
            ->assertRedirect(route('management.users.index'))
            ->assertSessionHas('success', 'Usuario creado.');

        $user = User::query()->where('email', 'ana.torres@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole(RoleSlug::Admin));
        $this->assertNull($user->password);
        $this->assertTrue($user->teams->contains($team));
    }

    public function test_store_requires_role_name_email_and_status(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.users.store'), [])
            ->assertSessionHasErrors([
                'role_id',
                'name' => 'El campo nombre es obligatorio.',
                'email',
                'is_active',
            ]);
    }

    public function test_store_rejects_teams_for_regular_users(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.users.store'), $this->payload([
                'team_ids' => [Team::factory()->create()->id],
            ]))
            ->assertSessionHasErrors(['team_ids' => 'Solo el personal de TI puede integrar un equipo.']);

        $this->assertDatabaseMissing('users', ['email' => 'ana.torres@example.com']);
    }

    public function test_update_keeps_the_password_when_left_empty(): void
    {
        $user = User::factory()->create(['password' => 'original-password']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.users.update', $user), $this->payload(['password' => '']))
            ->assertRedirect(route('management.users.index'));

        $this->assertSame('Ana Torres', $user->refresh()->name);
        $this->assertTrue(Hash::check('original-password', $user->password));
    }

    public function test_update_removes_team_memberships_when_user_stops_being_staff(): void
    {
        $user = User::factory()->admin()->create();
        $user->teams()->attach(Team::factory()->create(), ['is_lead' => true]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.users.update', $user), $this->payload())
            ->assertRedirect(route('management.users.index'));

        $this->assertTrue($user->refresh()->hasRole(RoleSlug::User));
        $this->assertCount(0, $user->teams);
    }

    public function test_super_admin_cannot_change_own_role_or_disable_own_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('management.users.update', $superAdmin), $this->payload([
                'role_id' => Role::findBySlug(RoleSlug::Admin)->id,
                'is_active' => false,
            ]))
            ->assertSessionHasErrors([
                'role_id' => 'No puedes cambiar tu propio rol.',
                'is_active' => 'No puedes desactivar tu propia cuenta.',
            ]);

        $this->assertTrue($superAdmin->refresh()->isSuperAdmin());
        $this->assertTrue($superAdmin->is_active);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'role_id' => Role::findBySlug(RoleSlug::User)->id,
            'name' => 'Ana Torres',
            'email' => 'ana.torres@example.com',
            'job_title' => 'Analista de soporte',
            'is_active' => true,
            'password' => '',
            'team_ids' => [],
            ...$overrides,
        ];
    }
}
