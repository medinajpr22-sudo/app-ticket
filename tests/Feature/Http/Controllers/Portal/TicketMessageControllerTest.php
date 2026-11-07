<?php

namespace Tests\Feature\Http\Controllers\Portal;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Office hours 07:00–18:00 in Bogotá: paused from 09:00 to 11:00 is two working hours.
     */
    public function test_answering_a_ticket_waiting_on_the_requester_gives_it_back_to_it(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 09:00', 'America/Bogota'));
        $ticket = Ticket::factory()->takenBy(User::factory()->admin()->create())->waitingOnRequester()->create([
            'first_responded_at' => now(),
            'resolution_due_at' => CarbonImmutable::parse('2026-11-09 17:00', 'America/Bogota')->utc(),
        ]);
        $ticket->businessCalendar->update(['timezone' => 'America/Bogota']);

        $this->travelTo(CarbonImmutable::parse('2026-11-09 11:00', 'America/Bogota'));

        $this->actingAs($ticket->requester)
            ->post(route('portal.tickets.messages.store', $ticket), ['body' => 'Ya reinicié el equipo y sigue igual.'])
            ->assertRedirect(route('portal.tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame(TicketStatus::InProgress, $ticket->status);
        $this->assertNull($ticket->sla_paused_at);
        $this->assertSame('2026-11-10 08:00', $ticket->resolution_due_at->setTimezone('America/Bogota')->format('Y-m-d H:i'));
    }

    public function test_someone_else_cannot_answer_the_ticket(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('portal.tickets.messages.store', Ticket::factory()->create()), ['body' => 'Hola'])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_messages', 0);
    }
}
