<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The default policy gives critical tickets 30 working minutes for the first
 * response and 4 hours for the resolution; office hours 07:00–18:00 in Bogotá.
 */
class TicketPriorityControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, ServiceLevelSeeder::class]);
        $this->travelTo(CarbonImmutable::parse('2026-11-09 09:00', 'America/Bogota'));

        $team = Team::factory()->create();
        $this->agent = User::factory()->admin()->create();
        $this->agent->teams()->attach($team);
        $this->ticket = Ticket::factory()->takenBy($this->agent)->create(['team_id' => $team->id]);
    }

    public function test_raising_the_priority_recalculates_the_deadlines_from_creation(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 09:10', 'America/Bogota'));

        $this->actingAs($this->agent)
            ->put(route('desk.tickets.priority.update', $this->ticket), [
                'priority' => TicketPriority::Critical->value,
                'reason' => 'Afecta a pacientes',
            ])
            ->assertRedirect(route('desk.tickets.show', $this->ticket))
            ->assertSessionHas('success', 'Prioridad actualizada.');

        $this->ticket->refresh();

        $this->assertSame(TicketPriority::Critical, $this->ticket->priority);
        $this->assertSame('09:30', $this->ticket->first_response_due_at->setTimezone('America/Bogota')->format('H:i'));
        $this->assertSame('13:00', $this->ticket->resolution_due_at->setTimezone('America/Bogota')->format('H:i'));
        $this->assertSame(
            'Prioridad cambiada de Media a Crítica. Motivo: Afecta a pacientes',
            $this->ticket->messages()->where('type', TicketMessageType::InternalNote)->sole()->body,
        );
    }

    public function test_time_spent_waiting_on_the_requester_is_still_added(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 10:00', 'America/Bogota'));
        $this->ticket->statusChanges()->create([
            'from_status' => TicketStatus::InProgress,
            'to_status' => TicketStatus::WaitingOnRequester,
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-11-09 11:00', 'America/Bogota'));
        $this->ticket->statusChanges()->create([
            'from_status' => TicketStatus::WaitingOnRequester,
            'to_status' => TicketStatus::InProgress,
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-11-09 12:00', 'America/Bogota'));

        $this->actingAs($this->agent)
            ->put(route('desk.tickets.priority.update', $this->ticket), ['priority' => TicketPriority::Critical->value]);

        $this->assertSame('14:00', $this->ticket->refresh()->resolution_due_at->setTimezone('America/Bogota')->format('H:i'));
    }

    public function test_the_requester_cannot_change_the_priority(): void
    {
        $this->actingAs($this->ticket->requester)
            ->put(route('desk.tickets.priority.update', $this->ticket), ['priority' => TicketPriority::Critical->value])
            ->assertForbidden();

        $this->assertSame(TicketPriority::Medium, $this->ticket->refresh()->priority);
    }
}
