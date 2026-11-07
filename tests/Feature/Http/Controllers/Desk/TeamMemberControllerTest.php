<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->team = Team::factory()->create(['name' => 'Web master']);
        $this->lead = User::factory()->admin()->create(['name' => 'Juan Pablo']);
        $this->lead->teams()->attach($this->team, ['is_lead' => true]);
    }

    public function test_a_lead_adds_an_assistant_to_their_team(): void
    {
        $this->actingAs($this->lead)
            ->get(route('desk.teams.members.create', $this->team))
            ->assertOk();

        $this->actingAs($this->lead)
            ->post(route('desk.teams.members.store', $this->team), $this->assistant())
            ->assertRedirect(route('desk.teams.show', $this->team))
            ->assertSessionHas('success', 'Catalina Ruiz ya es parte de Web master.');

        $catalina = User::query()->where('email', 'catalina.ruiz@example.com')->sole();

        $this->assertTrue($catalina->hasRole(RoleSlug::Admin));
        $this->assertTrue($catalina->is_active);
        $this->assertTrue($catalina->belongsToTeam($this->team->id));
        $this->assertFalse($catalina->leadsTeam($this->team->id));
        $this->assertSame('Auxiliar web master', $catalina->job_title);
    }

    public function test_the_lead_cannot_choose_the_role_nor_make_a_lead(): void
    {
        $this->actingAs($this->lead)->post(route('desk.teams.members.store', $this->team), [
            ...$this->assistant(),
            'role_id' => 1,
            'is_lead' => true,
        ]);

        $catalina = User::query()->where('email', 'catalina.ruiz@example.com')->sole();

        $this->assertTrue($catalina->hasRole(RoleSlug::Admin));
        $this->assertFalse($catalina->leadsTeam($this->team->id));
    }

    public function test_a_lead_cannot_add_people_to_a_team_they_do_not_lead(): void
    {
        $otherTeam = Team::factory()->create();
        $this->lead->teams()->attach($otherTeam, ['is_lead' => false]);

        $this->actingAs($this->lead)
            ->post(route('desk.teams.members.store', $otherTeam), $this->assistant())
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'catalina.ruiz@example.com']);
    }

    public function test_assistants_and_requesters_cannot_add_members(): void
    {
        $assistant = User::factory()->admin()->create();
        $assistant->teams()->attach($this->team);

        foreach ([$assistant, User::factory()->create()] as $user) {
            $this->actingAs($user)
                ->post(route('desk.teams.members.store', $this->team), $this->assistant())
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('users', ['email' => 'catalina.ruiz@example.com']);
    }

    public function test_the_email_must_not_belong_to_someone_else(): void
    {
        User::factory()->create(['email' => 'catalina.ruiz@example.com']);

        $this->actingAs($this->lead)
            ->post(route('desk.teams.members.store', $this->team), $this->assistant())
            ->assertSessionHasErrors(['email']);
    }

    /**
     * @return array<string, string>
     */
    private function assistant(): array
    {
        return [
            'name' => 'Catalina Ruiz',
            'email' => 'catalina.ruiz@example.com',
            'job_title' => 'Auxiliar web master',
            'password' => 'password-segura',
        ];
    }
}
