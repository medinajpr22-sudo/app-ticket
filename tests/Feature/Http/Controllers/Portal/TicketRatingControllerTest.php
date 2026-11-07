<?php

namespace Tests\Feature\Http\Controllers\Portal;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketRatingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_the_requester_rates_a_resolved_ticket(): void
    {
        $ticket = Ticket::factory()->resolved()->create();

        $this->actingAs($ticket->requester)
            ->post(route('portal.tickets.rating.store', $ticket), ['score' => 5, 'comment' => 'Muy rápido'])
            ->assertRedirect(route('portal.tickets.show', $ticket))
            ->assertSessionHas('success', 'Gracias por tu calificación.');

        $this->assertDatabaseHas('ticket_ratings', ['ticket_id' => $ticket->id, 'score' => 5, 'comment' => 'Muy rápido']);
    }

    public function test_an_open_ticket_cannot_be_rated_yet(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($ticket->requester)
            ->post(route('portal.tickets.rating.store', $ticket), ['score' => 4])
            ->assertForbidden();
    }

    public function test_a_ticket_is_rated_only_once(): void
    {
        $ticket = Ticket::factory()->resolved()->create();
        $ticket->rating()->create(['score' => 2]);

        $this->actingAs($ticket->requester)
            ->post(route('portal.tickets.rating.store', $ticket), ['score' => 5])
            ->assertForbidden();

        $this->assertSame(2, $ticket->rating()->value('score'));
    }

    public function test_the_score_goes_from_one_to_five(): void
    {
        $ticket = Ticket::factory()->resolved()->create();

        $this->actingAs($ticket->requester)
            ->post(route('portal.tickets.rating.store', $ticket), ['score' => 6])
            ->assertSessionHasErrors(['score' => 'El campo calificación debe estar entre 1 y 5.']);
    }

    public function test_someone_else_cannot_rate_the_ticket(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('portal.tickets.rating.store', Ticket::factory()->resolved()->create()), ['score' => 1])
            ->assertForbidden();
    }
}
