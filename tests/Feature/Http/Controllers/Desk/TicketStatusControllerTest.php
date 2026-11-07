<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tickets use office hours Monday to Friday, 07:00–18:00 in Bogotá.
 */
class TicketStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-09 09:00', 'America/Bogota'));

        $team = Team::factory()->create();
        $this->agent = User::factory()->admin()->create();
        $this->agent->teams()->attach($team);

        $this->ticket = Ticket::factory()->takenBy($this->agent)->create([
            'team_id' => $team->id,
            'first_responded_at' => now(),
            'resolution_due_at' => CarbonImmutable::parse('2026-11-09 17:00', 'America/Bogota')->utc(),
        ]);
        $this->ticket->businessCalendar->update(['timezone' => 'America/Bogota']);
    }

    public function test_waiting_on_the_requester_pauses_the_sla_and_resuming_moves_the_deadline(): void
    {
        $this->actingAs($this->agent)
            ->put(route('desk.tickets.status.update', $this->ticket), ['status' => TicketStatus::WaitingOnRequester->value])
            ->assertRedirect(route('desk.tickets.show', $this->ticket));

        $this->assertNotNull($this->ticket->refresh()->sla_paused_at);

        $this->travelTo(CarbonImmutable::parse('2026-11-09 11:00', 'America/Bogota'));

        $this->actingAs($this->agent)
            ->put(route('desk.tickets.status.update', $this->ticket), ['status' => TicketStatus::InProgress->value]);

        $this->ticket->refresh();

        $this->assertNull($this->ticket->sla_paused_at);
        $this->assertSame('2026-11-10 08:00', $this->ticket->resolution_due_at->setTimezone('America/Bogota')->format('Y-m-d H:i'));
    }

    public function test_resolving_records_when_it_happened(): void
    {
        $this->actingAs($this->agent)
            ->put(route('desk.tickets.status.update', $this->ticket), ['status' => TicketStatus::Resolved->value]);

        $this->ticket->refresh();

        $this->assertSame(TicketStatus::Resolved, $this->ticket->status);
        $this->assertTrue($this->ticket->resolved_at->equalTo(now()));
        $this->assertDatabaseHas('ticket_status_changes', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent->id,
            'from_status' => TicketStatus::InProgress->value,
            'to_status' => TicketStatus::Resolved->value,
        ]);
    }

    public function test_agents_cannot_set_statuses_reserved_for_the_system(): void
    {
        $this->actingAs($this->agent)
            ->put(route('desk.tickets.status.update', $this->ticket), ['status' => TicketStatus::Closed->value])
            ->assertSessionHasErrors(['status']);

        $this->assertSame(TicketStatus::InProgress, $this->ticket->refresh()->status);
    }

    public function test_a_requester_cannot_change_the_status_from_the_desk(): void
    {
        $this->actingAs($this->ticket->requester)
            ->put(route('desk.tickets.status.update', $this->ticket), ['status' => TicketStatus::Resolved->value])
            ->assertForbidden();
    }
}
