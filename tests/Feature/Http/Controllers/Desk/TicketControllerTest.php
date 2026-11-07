<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketStatus;
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

    public function test_the_inbox_puts_the_deadline_that_comes_first_on_top(): void
    {
        $this->newTicket('Vence mañana', ['first_response_due_at' => now()->addDay()]);
        $this->newTicket('Vence en una hora', ['first_response_due_at' => now()->addHour()]);
        $this->newTicket('Resolución en dos horas', [
            'assignee_id' => $this->agent->id,
            'status' => TicketStatus::InProgress,
            'first_responded_at' => now()->subHour(),
            'first_response_due_at' => now()->subMinutes(30),
            'resolution_due_at' => now()->addHours(2),
        ]);
        $this->newTicket('Esperando al usuario', [
            'status' => TicketStatus::WaitingOnRequester,
            'sla_paused_at' => now(),
            'first_response_due_at' => now()->subDay(),
        ]);

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tickets.data.0.subject', 'Vence en una hora')
                ->where('tickets.data.1.subject', 'Resolución en dos horas')
                ->where('tickets.data.2.subject', 'Vence mañana')
                ->where('tickets.data.3.subject', 'Esperando al usuario'));
    }

    public function test_finished_tickets_go_last_when_every_status_is_listed(): void
    {
        $this->newTicket('Cerrado hace poco', ['status' => TicketStatus::Closed, 'closed_at' => now(), 'first_response_due_at' => now()->subWeek()]);
        $this->newTicket('Abierto', ['first_response_due_at' => now()->addDay()]);

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index', ['status' => 'all']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tickets.data.0.subject', 'Abierto')
                ->where('tickets.data.1.subject', 'Cerrado hace poco'));
    }

    public function test_the_inbox_finds_a_ticket_by_its_number_or_its_subject(): void
    {
        $vpn = $this->newTicket('VPN caída en sede norte');
        $this->newTicket('Impresora sin tóner');

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index', ['search' => "#{$vpn->id}"]))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 1)->where('tickets.data.0.id', $vpn->id));

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index', ['search' => 'tóner']))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 1)->where('tickets.data.0.subject', 'Impresora sin tóner'));
    }

    public function test_the_inbox_filters_by_status_and_unassigned(): void
    {
        $this->newTicket('Recibido sin tomar');
        $this->newTicket('En proceso', ['assignee_id' => $this->agent->id, 'status' => TicketStatus::InProgress]);

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index', ['status' => TicketStatus::Received->value]))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 1)->where('tickets.data.0.subject', 'Recibido sin tomar'));

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index', ['view' => 'unassigned']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tickets.data', 1)
                ->where('tickets.data.0.subject', 'Recibido sin tomar')
                ->where('counts.unassigned', 1)
                ->where('counts.mine', 1));
    }

    public function test_the_inbox_only_offers_the_current_statuses(): void
    {
        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index'))
            ->assertInertia(fn (Assert $page) => $page->where('statuses', [
                ['value' => 'received', 'label' => 'Recibido'],
                ['value' => 'in_progress', 'label' => 'En proceso'],
                ['value' => 'waiting_on_requester', 'label' => 'Esperando al usuario'],
                ['value' => 'resolved', 'label' => 'Resuelto'],
                ['value' => 'closed', 'label' => 'Cerrado'],
            ]));

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index', ['status' => TicketStatus::Cancelled->value]))
            ->assertSessionHasErrors(['status']);
    }

    public function test_a_lead_cannot_filter_by_a_team_they_do_not_belong_to(): void
    {
        $otherTeam = Team::factory()->create();
        Ticket::factory()->create(['team_id' => $otherTeam->id]);
        $this->agent->teams()->updateExistingPivot($this->team->id, ['is_lead' => true]);

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.index', ['team' => $otherTeam->id]))
            ->assertSessionHasErrors(['team']);
    }

    public function test_the_inbox_never_lists_every_ticket_even_for_a_super_admin(): void
    {
        Ticket::factory()->create(['team_id' => $this->team->id]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('desk.tickets.index'))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 0)->where('teams', []));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function newTicket(string $subject, array $attributes = []): Ticket
    {
        return Ticket::factory()->create(['team_id' => $this->team->id, 'subject' => $subject, ...$attributes]);
    }
}
