<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Database\Seeders\MessageTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAssignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $lead;

    private User $teammate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, MessageTemplateSeeder::class]);

        $this->team = Team::factory()->create(['name' => 'Redes y Conectividad']);
        $this->lead = User::factory()->admin()->create(['name' => 'Luis Pardo']);
        $this->teammate = User::factory()->admin()->create(['name' => 'Ana Torres']);
        $this->team->members()->attach([$this->lead->id => ['is_lead' => true], $this->teammate->id => ['is_lead' => false]]);
    }

    public function test_reassigning_hands_the_ticket_to_a_teammate_and_leaves_a_note(): void
    {
        $ticket = Ticket::factory()->takenBy($this->lead)->create(['team_id' => $this->team->id]);

        $this->actingAs($this->lead)
            ->put(route('desk.tickets.assignment.update', $ticket), ['assignee_id' => $this->teammate->id, 'reason' => 'Conoce la VPN'])
            ->assertRedirect(route('desk.tickets.show', $ticket))
            ->assertSessionHas('success', 'Ticket reasignado.');

        $this->assertTrue($ticket->refresh()->assignee()->is($this->teammate));
        $this->assertSame('Asignado a Ana Torres. Motivo: Conoce la VPN', $ticket->messages()->where('type', TicketMessageType::InternalNote)->sole()->body);
        $this->assertDatabaseHas('ticket_assignments', ['ticket_id' => $ticket->id, 'assignee_id' => $this->teammate->id, 'user_id' => $this->lead->id]);
    }

    public function test_assigning_an_untaken_ticket_greets_the_requester_in_the_assignee_name(): void
    {
        $ticket = Ticket::factory()->create(['team_id' => $this->team->id]);

        $this->actingAs($this->lead)
            ->put(route('desk.tickets.assignment.update', $ticket), ['assignee_id' => $this->teammate->id]);

        $ticket->refresh();
        $greeting = $ticket->messages()->where('type', TicketMessageType::Reply)->sole();

        $this->assertSame(TicketStatus::InProgress, $ticket->status);
        $this->assertSame($this->teammate->id, $greeting->author_id);
        $this->assertStringContainsString('soy Ana Torres del equipo Redes y Conectividad', $greeting->body);
    }

    public function test_the_new_assignee_must_belong_to_the_team(): void
    {
        $ticket = Ticket::factory()->create(['team_id' => $this->team->id]);

        $this->actingAs($this->lead)
            ->put(route('desk.tickets.assignment.update', $ticket), ['assignee_id' => User::factory()->admin()->create()->id])
            ->assertSessionHasErrors(['assignee_id' => 'Elige a un integrante activo de este equipo.']);
    }

    public function test_transferring_sends_the_ticket_to_the_other_team_and_tells_the_requester(): void
    {
        $ticket = Ticket::factory()->takenBy($this->lead)->create(['team_id' => $this->team->id]);
        $infrastructure = Team::factory()->create(['name' => 'Infraestructura y Hardware']);

        $this->actingAs($this->lead)
            ->post(route('desk.tickets.transfer', $ticket), ['team_id' => $infrastructure->id, 'reason' => 'Es el equipo, no la red'])
            ->assertRedirect(route('desk.tickets.index'))
            ->assertSessionHas('success', 'Ticket transferido.');

        $ticket->refresh();

        $this->assertSame($infrastructure->id, $ticket->team_id);
        $this->assertNull($ticket->assignee_id);
        $this->assertSame(TicketStatus::New, $ticket->status);
        $this->assertSame(
            'Tu solicitud pasó al equipo Infraestructura y Hardware, que continuará con ella.',
            $ticket->messages()->where('type', TicketMessageType::Automated)->sole()->body,
        );
        $this->assertSame(
            'Transferido de Redes y Conectividad a Infraestructura y Hardware. Motivo: Es el equipo, no la red',
            $ticket->messages()->where('type', TicketMessageType::InternalNote)->sole()->body,
        );
    }

    public function test_a_transfer_needs_a_reason_and_another_team(): void
    {
        $ticket = Ticket::factory()->create(['team_id' => $this->team->id]);

        $this->actingAs($this->lead)
            ->post(route('desk.tickets.transfer', $ticket), ['team_id' => $this->team->id, 'reason' => ''])
            ->assertSessionHasErrors([
                'team_id' => 'El ticket ya está en ese equipo.',
                'reason',
            ]);
    }

    public function test_the_requester_cannot_reassign_or_transfer(): void
    {
        $ticket = Ticket::factory()->create(['team_id' => $this->team->id]);

        $this->actingAs($ticket->requester)
            ->put(route('desk.tickets.assignment.update', $ticket), ['assignee_id' => $this->teammate->id])
            ->assertForbidden();
    }
}
