<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketHistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-13 17:00', 'America/Bogota'));

        $this->team = Team::factory()->create();
        $this->agent = User::factory()->admin()->create();
        $this->agent->teams()->attach($this->team);
    }

    public function test_the_history_lists_what_the_teams_finished_the_most_recent_first(): void
    {
        $this->finished('Resuelto ayer', TicketStatus::Resolved, now()->subDay());
        $this->finished('Cerrado hoy', TicketStatus::Closed, now()->subHour(), ['rating' => 5]);
        $this->finished('Cerrado la semana pasada', TicketStatus::Closed, now()->subWeek());
        Ticket::factory()->create(['team_id' => $this->team->id, 'subject' => 'Todavía abierto']);
        Ticket::factory()->closed()->create(['subject' => 'De otro equipo']);

        $this->actingAs($this->agent)
            ->get(route('desk.history.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Desk/Tickets/History')
                ->has('tickets.data', 3)
                ->where('tickets.data.0.subject', 'Cerrado hoy')
                ->where('tickets.data.0.rating.score', 5)
                ->where('tickets.data.0.closed_by.name', $this->agent->name)
                ->where('tickets.data.1.subject', 'Resuelto ayer')
                ->where('tickets.data.2.subject', 'Cerrado la semana pasada')
                ->where('counts', ['all' => 3, 'mine' => 3]));
    }

    public function test_the_history_filters_by_status_agent_search_and_period(): void
    {
        $colleague = User::factory()->admin()->create();
        $colleague->teams()->attach($this->team);

        $this->finished('VPN de la sede norte', TicketStatus::Resolved, now()->subDay());
        $this->finished('Impresora sin tóner', TicketStatus::Closed, now()->subDays(2));
        $this->finished('Correo de un colega', TicketStatus::Closed, now()->subDays(40), ['assignee' => $colleague]);

        $this->actingAs($this->agent)
            ->get(route('desk.history.index', ['status' => TicketStatus::Closed->value]))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 2));

        $this->actingAs($this->agent)
            ->get(route('desk.history.index', ['view' => 'mine']))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 2)->where('counts.mine', 2));

        $this->actingAs($this->agent)
            ->get(route('desk.history.index', ['search' => 'tóner']))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 1)->where('tickets.data.0.subject', 'Impresora sin tóner'));

        $this->actingAs($this->agent)
            ->get(route('desk.history.index', ['from' => '2026-11-01', 'to' => '2026-11-13']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tickets.data', 2)
                ->where('filters.from', '2026-11-01')
                ->where('filters.to', '2026-11-13'));
    }

    public function test_requesters_have_no_history_of_the_desk(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('desk.history.index'))
            ->assertForbidden();
    }

    public function test_the_history_cannot_be_filtered_by_a_team_of_others(): void
    {
        $this->actingAs($this->agent)
            ->get(route('desk.history.index', ['team' => Team::factory()->create()->id]))
            ->assertSessionHasErrors(['team']);
    }

    /**
     * @param  array{rating?: int, assignee?: User}  $options
     */
    private function finished(string $subject, TicketStatus $status, CarbonInterface $resolvedAt, array $options = []): Ticket
    {
        $assignee = $options['assignee'] ?? $this->agent;

        $ticket = Ticket::factory()->takenBy($assignee)->create([
            'team_id' => $this->team->id,
            'subject' => $subject,
            'status' => $status,
            'resolved_at' => $resolvedAt->utc(),
            'closed_at' => $status === TicketStatus::Closed ? $resolvedAt->utc() : null,
            'closed_by_id' => $status === TicketStatus::Closed ? $assignee->id : null,
        ]);

        if (isset($options['rating'])) {
            $ticket->rating()->create(['score' => $options['rating']]);
        }

        return $ticket;
    }
}
