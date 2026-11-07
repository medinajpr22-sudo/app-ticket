<?php

namespace Tests\Feature\Http\Controllers\Portal;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketClosureControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->freezeSecond();

        $this->requester = User::factory()->create(['name' => 'Carolina Méndez']);
        $this->agent = User::factory()->admin()->create();
    }

    public function test_the_requester_closes_a_ticket_they_no_longer_need_help_with(): void
    {
        Notification::fake();
        $ticket = Ticket::factory()->for($this->requester, 'requester')->takenBy($this->agent)->create();

        $this->actingAs($this->requester)
            ->post(route('portal.tickets.closure.store', $ticket))
            ->assertRedirect(route('portal.tickets.show', $ticket))
            ->assertSessionHas('success', 'Cerraste el ticket. ¡Gracias por avisarnos!');

        $ticket->refresh();

        $this->assertSame(TicketStatus::Closed, $ticket->status);
        $this->assertTrue($ticket->closedBy()->is($this->requester));
        $this->assertTrue($ticket->closed_at->equalTo(now()));
        $this->assertTrue($ticket->resolved_at->equalTo(now()));
        $this->assertDatabaseHas('ticket_status_changes', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->requester->id,
            'from_status' => TicketStatus::InProgress->value,
            'to_status' => TicketStatus::Closed->value,
        ]);
        Notification::assertNothingSent();
    }

    public function test_confirming_the_solution_of_it_keeps_its_resolution_time(): void
    {
        $resolvedAt = now()->subHours(3);
        $ticket = Ticket::factory()->for($this->requester, 'requester')->takenBy($this->agent)->resolved()->create(['resolved_at' => $resolvedAt]);

        $this->actingAs($this->requester)->post(route('portal.tickets.closure.store', $ticket));

        $ticket->refresh();

        $this->assertSame(TicketStatus::Closed, $ticket->status);
        $this->assertTrue($ticket->resolved_at->equalTo($resolvedAt));
    }

    public function test_the_closed_ticket_shows_who_closed_it_and_can_be_rated(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->takenBy($this->agent)->create();
        $this->actingAs($this->requester)->post(route('portal.tickets.closure.store', $ticket));

        $this->actingAs($this->requester)
            ->get(route('portal.tickets.show', $ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->where('ticket.status.label', 'Cerrado')
                ->where('ticket.closed_by.name', 'Carolina Méndez')
                ->where('ticket.closed_at', now()->toIso8601String())
                ->where('can.close', false)
                ->where('can.reply', false)
                ->where('can.rate', true)
                ->where('can.archive', true));
    }

    public function test_nobody_closes_a_ticket_of_someone_else(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();

        $this->actingAs(User::factory()->create())
            ->post(route('portal.tickets.closure.store', $ticket))
            ->assertForbidden();

        $this->assertSame(TicketStatus::Received, $ticket->refresh()->status);
    }

    public function test_a_closed_ticket_cannot_be_closed_again(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->closed()->create();

        $this->actingAs($this->requester)
            ->post(route('portal.tickets.closure.store', $ticket))
            ->assertForbidden();
    }

    public function test_reopening_a_resolved_ticket_returns_it_to_the_agent(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->takenBy($this->agent)->resolved()->create();

        $this->actingAs($this->requester)
            ->delete(route('portal.tickets.resolution.destroy', $ticket))
            ->assertRedirect(route('portal.tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame(TicketStatus::InProgress, $ticket->status);
        $this->assertNull($ticket->resolved_at);
    }
}
