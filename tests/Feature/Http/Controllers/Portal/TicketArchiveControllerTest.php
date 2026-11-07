<?php

namespace Tests\Feature\Http\Controllers\Portal;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketArchiveControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->requester = User::factory()->create();
    }

    public function test_removing_a_finished_ticket_hides_it_from_the_list_without_deleting_it(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->closed()->create(['subject' => 'Monitor parpadea']);

        $this->actingAs($this->requester)
            ->post(route('portal.tickets.archive.store', $ticket))
            ->assertRedirect(route('portal.tickets.index'))
            ->assertSessionHas('success', 'Quitaste el ticket de tu lista. Lo encuentras en Archivados.');

        $this->assertNotNull($ticket->refresh()->requester_archived_at);
        $this->assertModelExists($ticket);

        $this->actingAs($this->requester)
            ->get(route('portal.tickets.index'))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 0));

        $this->actingAs($this->requester)
            ->get(route('portal.tickets.index', ['status' => 'archived']))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 1)->where('tickets.data.0.subject', 'Monitor parpadea'));
    }

    public function test_only_closed_tickets_can_be_removed_from_the_list(): void
    {
        foreach ([Ticket::factory()->for($this->requester, 'requester')->create(), Ticket::factory()->for($this->requester, 'requester')->resolved()->create()] as $ticket) {
            $this->actingAs($this->requester)
                ->post(route('portal.tickets.archive.store', $ticket))
                ->assertForbidden();

            $this->assertNotSame(TicketStatus::Closed, $ticket->refresh()->status);
            $this->assertNull($ticket->requester_archived_at);
        }
    }

    public function test_the_requester_can_bring_an_archived_ticket_back(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->closed()->create(['requester_archived_at' => now()]);

        $this->actingAs($this->requester)
            ->delete(route('portal.tickets.archive.destroy', $ticket))
            ->assertRedirect(route('portal.tickets.show', $ticket));

        $this->assertNull($ticket->refresh()->requester_archived_at);
    }

    public function test_archived_tickets_stay_in_the_desk(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->closed()->create(['requester_archived_at' => now()]);
        $agent = User::factory()->admin()->create();
        $agent->teams()->attach($ticket->team_id);

        $this->actingAs($agent)
            ->get(route('desk.tickets.index', ['status' => 'all']))
            ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 1));
    }

    public function test_nobody_archives_a_ticket_of_someone_else(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->closed()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('portal.tickets.archive.store', $ticket))
            ->assertForbidden();

        $this->assertNull($ticket->refresh()->requester_archived_at);
    }
}
