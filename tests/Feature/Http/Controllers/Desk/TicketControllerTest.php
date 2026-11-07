<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Models\Ticket;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->team = Team::factory()->create();
        $this->agent = User::factory()->admin()->create();
        $this->agent->teams()->attach($this->team);
    }

    public function test_a_requester_without_desk_access_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('desk.tickets.index'))
            ->assertForbidden();
    }

    public function test_index_lists_only_open_tickets_of_the_agent_teams(): void
    {
        Ticket::factory()->create(['team_id' => $this->team->id, 'subject' => 'VPN caída en sede norte']);
        Ticket::factory()->resolved()->create(['team_id' => $this->team->id, 'subject' => 'Ticket ya resuelto']);
        Ticket::factory()->create(['subject' => 'Ticket de otro equipo']);

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Desk/Tickets/Index')
                ->has('tickets.data', 1)
                ->where('tickets.data.0.subject', 'VPN caída en sede norte')
                ->where('counts.unassigned', 1));
    }

    public function test_index_filters_the_tickets_assigned_to_me(): void
    {
        Ticket::factory()->takenBy($this->agent)->create(['team_id' => $this->team->id, 'subject' => 'Mi ticket']);
        Ticket::factory()->create(['team_id' => $this->team->id, 'subject' => 'Sin asignar']);

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index', ['view' => 'mine']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tickets.data', 1)
                ->where('tickets.data.0.subject', 'Mi ticket'));
    }

    public function test_show_includes_internal_notes_for_the_team(): void
    {
        $ticket = Ticket::factory()->create(['team_id' => $this->team->id]);
        $ticket->messages()->create(['author_id' => $this->agent->id, 'type' => TicketMessageType::InternalNote, 'body' => 'Nota solo para TI']);

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Desk/Tickets/Show')
                ->where('ticket.messages.0.type', TicketMessageType::InternalNote->value)
                ->where('can.take', true));
    }

    public function test_show_is_forbidden_for_tickets_of_other_teams(): void
    {
        $this->actingAs($this->agent)
            ->get(route('desk.tickets.show', Ticket::factory()->create()))
            ->assertForbidden();
    }
}
