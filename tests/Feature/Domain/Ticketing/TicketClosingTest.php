<?php

namespace Tests\Feature\Domain\Ticketing;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketActivity;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Exceptions\TicketIsClosed;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Notifications\TicketActivityNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Only IT closes tickets, and a closed ticket is final: when the problem
 * comes back, the requester opens a new one.
 */
class TicketClosingTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private User $requester;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->freezeSecond();

        $team = Team::factory()->create();
        $this->agent = User::factory()->admin()->create(['name' => 'Luis Pardo']);
        $this->agent->teams()->attach($team);
        $this->requester = User::factory()->create(['name' => 'Carolina Méndez']);
        $this->ticket = Ticket::factory()->for($this->requester, 'requester')->takenBy($this->agent)->create(['team_id' => $team->id]);
    }

    public function test_the_agent_closes_the_ticket_and_the_requester_is_told(): void
    {
        Notification::fake();

        $this->actingAs($this->agent)
            ->put(route('desk.tickets.status.update', $this->ticket), ['status' => TicketStatus::Closed->value])
            ->assertSessionHasNoErrors();

        $this->ticket->refresh();

        $this->assertSame(TicketStatus::Closed, $this->ticket->status);
        $this->assertTrue($this->ticket->closedBy()->is($this->agent));
        $this->assertTrue($this->ticket->closed_at->equalTo(now()));
        Notification::assertSentTo(
            $this->requester,
            TicketActivityNotification::class,
            fn (TicketActivityNotification $notification): bool => $notification->activity === TicketActivity::Closed,
        );
    }

    public function test_the_requester_has_no_way_to_close_the_ticket(): void
    {
        $this->actingAs($this->requester)
            ->get(route('portal.tickets.show', $this->ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->where('can', ['reply' => true, 'reopen' => false, 'archive' => false, 'unarchive' => false, 'rate' => false]));

        $this->actingAs($this->requester)
            ->put(route('desk.tickets.status.update', $this->ticket), ['status' => TicketStatus::Closed->value])
            ->assertForbidden();

        $this->actingAs($this->requester)
            ->post("/portal/tickets/{$this->ticket->id}/closure")
            ->assertNotFound();

        $this->assertSame(TicketStatus::InProgress, $this->ticket->refresh()->status);
    }

    public function test_a_closed_ticket_cannot_be_reopened_nor_written_in(): void
    {
        $this->close();

        $this->actingAs($this->requester)
            ->post(route('portal.tickets.messages.store', $this->ticket), ['body' => 'Volvió a fallar'])
            ->assertForbidden();
        $this->actingAs($this->requester)
            ->delete(route('portal.tickets.resolution.destroy', $this->ticket))
            ->assertForbidden();
        $this->actingAs($this->agent)
            ->put(route('desk.tickets.status.update', $this->ticket), ['status' => TicketStatus::InProgress->value])
            ->assertForbidden();
        $this->actingAs($this->agent)
            ->post(route('desk.tickets.messages.store', $this->ticket), ['body' => 'Lo retomo'])
            ->assertForbidden();

        $this->assertSame(TicketStatus::Closed, $this->ticket->refresh()->status);
        $this->assertSame(0, $this->ticket->messages()->count());
    }

    public function test_no_part_of_the_application_can_reopen_a_closed_ticket(): void
    {
        $this->close();

        $this->expectException(TicketIsClosed::class);

        app(ChangeTicketStatus::class)->handle($this->ticket->refresh(), TicketStatus::InProgress, $this->agent);
    }

    public function test_the_closed_ticket_tells_the_requester_to_open_a_new_one(): void
    {
        $this->close();

        $this->actingAs($this->requester)
            ->get(route('portal.tickets.show', $this->ticket))
            ->assertInertia(fn (Assert $page) => $page
                ->where('ticket.status.label', 'Cerrado')
                ->where('ticket.closed_by.name', 'Luis Pardo')
                ->where('can.reply', false)
                ->where('can.reopen', false)
                ->where('can.rate', true)
                ->where('can.archive', true));
    }

    public function test_the_requester_can_still_say_a_resolved_ticket_is_not_solved(): void
    {
        $this->ticket->update(['status' => TicketStatus::Resolved, 'resolved_at' => now()]);

        $this->actingAs($this->requester)
            ->delete(route('portal.tickets.resolution.destroy', $this->ticket))
            ->assertRedirect(route('portal.tickets.show', $this->ticket));

        $this->ticket->refresh();

        $this->assertSame(TicketStatus::InProgress, $this->ticket->status);
        $this->assertNull($this->ticket->resolved_at);
    }

    private function close(): void
    {
        app(ChangeTicketStatus::class)->handle($this->ticket, TicketStatus::Closed, $this->agent);
    }
}
