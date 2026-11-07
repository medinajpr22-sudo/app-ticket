<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->team = Team::factory()->create();
        $this->agent = User::factory()->admin()->create();
        $this->agent->teams()->attach($this->team);
        $this->ticket = Ticket::factory()->takenBy($this->agent)->create(['team_id' => $this->team->id]);
    }

    public function test_a_reply_from_it_stops_the_first_response_clock(): void
    {
        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $this->ticket), ['body' => '¿Desde cuándo pasa?'])
            ->assertRedirect(route('desk.tickets.show', $this->ticket))
            ->assertSessionHas('success', 'Mensaje enviado.');

        $this->assertNotNull($this->ticket->refresh()->first_responded_at);
        $this->assertSame(TicketMessageType::Reply, $this->ticket->messages()->sole()->type);
    }

    public function test_the_chat_has_no_internal_notes(): void
    {
        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $this->ticket), ['body' => 'Revisar el firewall', 'is_internal' => true])
            ->assertRedirect(route('desk.tickets.show', $this->ticket));

        $this->assertSame(TicketMessageType::Reply, $this->ticket->messages()->sole()->type);
    }

    public function test_an_unassigned_ticket_must_be_taken_before_writing(): void
    {
        $untaken = Ticket::factory()->create(['team_id' => $this->team->id]);

        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $untaken), ['body' => 'Hola'])
            ->assertForbidden();

        $this->actingAs($this->agent)->post(route('desk.tickets.take', $untaken));

        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $untaken), ['body' => 'Ya estoy revisando'])
            ->assertRedirect(route('desk.tickets.show', $untaken));

        $this->assertTrue($untaken->messages()->where('body', 'Ya estoy revisando')->exists());
    }

    public function test_a_colleague_must_take_the_ticket_before_writing(): void
    {
        $colleague = User::factory()->admin()->create();
        $colleague->teams()->attach($this->team);

        $this->actingAs($colleague)
            ->post(route('desk.tickets.messages.store', $this->ticket), ['body' => 'Te ayudo con esto'])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_messages', 0);
    }

    public function test_the_desk_tells_the_page_who_may_write_and_who_must_take_the_ticket_first(): void
    {
        $colleague = User::factory()->admin()->create();
        $colleague->teams()->attach($this->team);

        $this->actingAs($this->agent)
            ->get(route('desk.tickets.show', $this->ticket))
            ->assertInertia(fn ($page) => $page->where('can.respond', true)->where('can.take', false));

        $this->actingAs($colleague)
            ->get(route('desk.tickets.show', $this->ticket))
            ->assertInertia(fn ($page) => $page->where('can.respond', false)->where('can.take', true));
    }

    public function test_nobody_writes_in_a_closed_ticket(): void
    {
        $this->ticket->update(['status' => TicketStatus::Closed, 'closed_at' => now()]);

        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $this->ticket), ['body' => 'Una cosa más'])
            ->assertForbidden();
    }

    public function test_the_requester_cannot_write_from_the_desk(): void
    {
        $this->actingAs($this->ticket->requester)
            ->post(route('desk.tickets.messages.store', $this->ticket), ['body' => 'Hola'])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_messages', 0);
    }
}
