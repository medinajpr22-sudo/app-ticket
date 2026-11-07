<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Models\Ticket;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $team = Team::factory()->create();
        $this->agent = User::factory()->admin()->create();
        $this->agent->teams()->attach($team);
        $this->ticket = Ticket::factory()->takenBy($this->agent)->create(['team_id' => $team->id]);
    }

    public function test_a_reply_from_it_stops_the_first_response_clock(): void
    {
        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $this->ticket), ['body' => '¿Desde cuándo pasa?', 'is_internal' => false])
            ->assertRedirect(route('desk.tickets.show', $this->ticket))
            ->assertSessionHas('success', 'Mensaje enviado.');

        $this->assertNotNull($this->ticket->refresh()->first_responded_at);
        $this->assertSame(TicketMessageType::Reply, $this->ticket->messages()->sole()->type);
    }

    public function test_an_internal_note_does_not_count_as_a_response(): void
    {
        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $this->ticket), ['body' => 'Revisar el firewall', 'is_internal' => true])
            ->assertSessionHas('success', 'Nota interna guardada.');

        $this->assertNull($this->ticket->refresh()->first_responded_at);
        $this->assertSame(TicketMessageType::InternalNote, $this->ticket->messages()->sole()->type);
    }

    public function test_a_ticket_must_be_taken_before_answering_the_requester(): void
    {
        $untaken = Ticket::factory()->create(['team_id' => $this->ticket->team_id]);

        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $untaken), ['body' => 'Hola', 'is_internal' => false])
            ->assertForbidden();

        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $untaken), ['body' => 'Revisar antes de tomarlo', 'is_internal' => true])
            ->assertRedirect(route('desk.tickets.show', $untaken));

        $this->assertSame(TicketMessageType::InternalNote, $untaken->messages()->sole()->type);
    }

    public function test_the_requester_cannot_write_internal_notes(): void
    {
        $this->actingAs($this->ticket->requester)
            ->post(route('desk.tickets.messages.store', $this->ticket), ['body' => 'Intento de nota', 'is_internal' => true])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_messages', 0);
    }
}
