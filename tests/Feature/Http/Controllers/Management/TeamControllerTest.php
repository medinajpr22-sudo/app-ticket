<?php

namespace Tests\Feature\Http\Controllers\Management;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Enums\TeamType;
use App\Domain\Organization\Models\Team;
use App\Domain\Shared\Enums\Icon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_without_team_management_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('management.teams.index'))
            ->assertForbidden();
    }

    public function test_index_lists_teams_with_leads_first(): void
    {
        $team = Team::factory()->create(['name' => 'Redes y Conectividad']);
        $team->members()->attach(User::factory()->admin()->create(['name' => 'Ana Torres']), ['is_lead' => false]);
        $team->members()->attach(User::factory()->admin()->create(['name' => 'Luis Pardo']), ['is_lead' => true]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('management.teams.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/Teams/Index')
                ->has('teams', 1)
                ->where('teams.0.members.0.name', 'Luis Pardo')
                ->where('teams.0.members.0.is_lead', true));
    }

    public function test_store_creates_team_with_members_and_lead(): void
    {
        $lead = User::factory()->admin()->create();
        $member = User::factory()->admin()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.teams.store'), $this->payload([
                'members' => [
                    ['user_id' => $lead->id, 'is_lead' => true],
                    ['user_id' => $member->id, 'is_lead' => false],
                ],
            ]))
            ->assertRedirect(route('management.teams.index'))
            ->assertSessionHas('success', 'Equipo creado.');

        $team = Team::query()->where('name', 'Redes y Conectividad')->firstOrFail();

        $this->assertSame(TeamType::GeneralSupport, $team->type);
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $lead->id, 'is_lead' => true]);
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $member->id, 'is_lead' => false]);
    }

    public function test_store_rejects_members_who_are_not_it_staff(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.teams.store'), $this->payload([
                'members' => [['user_id' => User::factory()->create()->id, 'is_lead' => false]],
            ]))
            ->assertSessionHasErrors(['members.0.user_id' => 'Solo el personal de TI puede integrar un equipo.']);

        $this->assertDatabaseCount('teams', 0);
    }

    public function test_store_rejects_a_duplicated_team_name(): void
    {
        Team::factory()->create(['name' => 'Redes y Conectividad']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.teams.store'), $this->payload())
            ->assertSessionHasErrors(['name' => 'El valor de nombre ya está en uso.']);
    }

    public function test_update_replaces_members_and_lead_flags(): void
    {
        $team = Team::factory()->create();
        $leavingMember = User::factory()->admin()->create();
        $stayingMember = User::factory()->admin()->create();
        $team->members()->attach([
            $leavingMember->id => ['is_lead' => true],
            $stayingMember->id => ['is_lead' => false],
        ]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.teams.update', $team), $this->payload([
                'members' => [['user_id' => $stayingMember->id, 'is_lead' => true]],
            ]))
            ->assertRedirect(route('management.teams.index'));

        $this->assertDatabaseMissing('team_user', ['team_id' => $team->id, 'user_id' => $leavingMember->id]);
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $stayingMember->id, 'is_lead' => true]);
        $this->assertSame('Redes y Conectividad', $team->refresh()->name);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Redes y Conectividad',
            'type' => TeamType::GeneralSupport->value,
            'icon' => Icon::Wifi->value,
            'description' => 'WiFi, VPN, telefonía y enlaces',
            'is_active' => true,
            'members' => [],
            ...$overrides,
        ];
    }
}
