<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Tickets use office hours Monday to Friday, 07:00–18:00 in Bogotá.
 */
class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $lead;

    private User $catalina;

    private BusinessCalendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-13 17:00', 'America/Bogota'));

        $this->calendar = BusinessCalendar::factory()->default()->withOfficeHours()->create();
        $this->team = Team::factory()->create(['name' => 'Web master']);
        $this->lead = User::factory()->admin()->create(['name' => 'Juan Pablo']);
        $this->lead->teams()->attach($this->team, ['is_lead' => true]);
        $this->catalina = User::factory()->admin()->create(['name' => 'Catalina Ruiz']);
        $this->catalina->teams()->attach($this->team);
    }

    public function test_my_team_opens_the_team_the_person_leads(): void
    {
        $this->actingAs($this->lead)
            ->get(route('desk.teams.index'))
            ->assertRedirect(route('desk.teams.show', $this->team));
    }

    public function test_the_lead_compares_how_each_member_performs(): void
    {
        $monday = CarbonImmutable::parse('2026-11-09 08:00', 'America/Bogota');

        $onTime = $this->ticketFor($this->catalina, $monday, [
            'first_responded_at' => $monday->addMinutes(30),
            'resolved_at' => $monday->addHours(2),
            'status' => TicketStatus::Closed,
        ]);
        $onTime->rating()->create(['score' => 5]);

        $late = $this->ticketFor($this->catalina, $monday, [
            'first_responded_at' => $monday->addMinutes(90),
            'resolved_at' => $monday->addHours(6),
            'status' => TicketStatus::Resolved,
        ]);
        $late->rating()->create(['score' => 3]);

        $this->ticketFor($this->catalina, $monday->addDay(), ['status' => TicketStatus::InProgress]);
        $this->ticketFor(null, $monday->addDay(), []);

        $this->actingAs($this->lead)
            ->get(route('desk.teams.show', $this->team))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Desk/Teams/Show')
                ->where('summary.total', 4)
                ->where('summary.resolved', 2)
                ->where('unassigned', 1)
                ->where('members.0.name', 'Juan Pablo')
                ->where('members.0.is_lead', true)
                ->where('members.1.name', 'Catalina Ruiz')
                ->where('members.1.metrics.total', 3)
                ->where('members.1.metrics.resolved', 2)
                ->where('members.1.metrics.resolution_compliance', 50)
                ->where('members.1.metrics.first_response_compliance', 50)
                ->where('members.1.metrics.average_first_response_minutes', 60)
                ->where('members.1.metrics.average_resolution_minutes', 240)
                ->where('members.1.metrics.average_rating', 4)
                ->where('members.1.metrics.ratings', 2)
                ->where('members.1.open_now', 1));
    }

    public function test_the_period_filters_the_numbers(): void
    {
        $this->ticketFor($this->catalina, CarbonImmutable::parse('2026-10-01 09:00', 'America/Bogota'), []);
        $this->ticketFor($this->catalina, CarbonImmutable::parse('2026-11-10 09:00', 'America/Bogota'), []);

        $this->actingAs($this->lead)
            ->get(route('desk.teams.show', [$this->team, 'from' => '2026-11-01', 'to' => '2026-11-13']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters', ['from' => '2026-11-01', 'to' => '2026-11-13'])
                ->where('summary.total', 1));

        $this->actingAs($this->lead)
            ->get(route('desk.teams.show', [$this->team, 'from' => '2025-01-01', 'to' => '2026-11-13']))
            ->assertSessionHasErrors(['from']);
    }

    public function test_a_lead_only_sees_the_teams_they_lead(): void
    {
        $otherTeam = Team::factory()->create();
        $this->lead->teams()->attach($otherTeam);

        $this->actingAs($this->lead)
            ->get(route('desk.teams.show', $otherTeam))
            ->assertForbidden();
    }

    public function test_assistants_have_no_team_page(): void
    {
        $this->actingAs($this->catalina)
            ->get(route('desk.teams.show', $this->team))
            ->assertForbidden();

        $this->actingAs($this->catalina)
            ->get(route('desk.teams.index'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function ticketFor(?User $assignee, CarbonImmutable $createdAt, array $attributes): Ticket
    {
        $ticket = Ticket::factory()->create([
            'team_id' => $this->team->id,
            'assignee_id' => $assignee?->id,
            'business_calendar_id' => $this->calendar->id,
            'first_response_due_at' => $createdAt->addHour()->utc(),
            'resolution_due_at' => $createdAt->addHours(4)->utc(),
            ...array_map(fn (mixed $value): mixed => $value instanceof CarbonImmutable ? $value->utc() : $value, $attributes),
        ]);

        $ticket->forceFill(['created_at' => $createdAt->utc()])->save();

        return $ticket;
    }
}
