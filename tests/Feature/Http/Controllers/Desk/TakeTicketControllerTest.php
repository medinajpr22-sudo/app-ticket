<?php

namespace Tests\Feature\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\MessageTemplate;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Database\Seeders\MessageTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TakeTicketControllerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, MessageTemplateSeeder::class]);
        $this->travelTo(CarbonImmutable::parse('2026-11-09 09:00', 'America/Bogota'));

        $this->team = Team::factory()->create(['name' => 'Redes y Conectividad']);
        $this->agent = User::factory()->admin()->create(['name' => 'Ana Torres']);
        $this->agent->teams()->attach($this->team);
    }

    public function test_taking_a_ticket_assigns_it_and_greets_the_requester(): void
    {
        $ticket = $this->newTicket();

        $this->actingAs($this->agent)
            ->post(route('desk.tickets.take', $ticket))
            ->assertRedirect(route('desk.tickets.show', $ticket))
            ->assertSessionHas('success', 'Tomaste el ticket.');

        $ticket->refresh();
        $greeting = $ticket->messages()->sole();

        $this->assertTrue($ticket->assignee()->is($this->agent));
        $this->assertSame(TicketStatus::InProgress, $ticket->status);
        $this->assertNotNull($ticket->first_responded_at);
        $this->assertSame(TicketMessageType::Reply, $greeting->type);
        $this->assertSame($this->agent->id, $greeting->author_id);
        $this->assertStringStartsWith(
            "Buenos días Carolina, soy Ana Torres del equipo Redes y Conectividad. Ya tomé tu caso #{$ticket->id}",
            $greeting->body,
        );
        $this->assertDatabaseHas('ticket_assignments', ['ticket_id' => $ticket->id, 'assignee_id' => $this->agent->id]);
    }

    public function test_the_team_greeting_replaces_the_general_one(): void
    {
        MessageTemplate::factory()->purpose(MessageTemplatePurpose::Greeting)->create([
            'team_id' => $this->team->id,
            'body' => 'Hola {{requester.first_name}}, Redes ya tiene tu caso.',
        ]);
        $ticket = $this->newTicket();

        $this->actingAs($this->agent)->post(route('desk.tickets.take', $ticket));

        $this->assertSame('Hola Carolina, Redes ya tiene tu caso.', $ticket->messages()->sole()->body);
    }

    public function test_a_ticket_someone_already_took_cannot_be_taken_again(): void
    {
        $colleague = User::factory()->admin()->create();
        $colleague->teams()->attach($this->team);
        $ticket = $this->newTicket(['assignee_id' => $colleague->id, 'status' => TicketStatus::InProgress]);

        $this->actingAs($this->agent)
            ->post(route('desk.tickets.take', $ticket))
            ->assertForbidden();

        $this->assertTrue($ticket->refresh()->assignee()->is($colleague));
    }

    public function test_an_agent_of_another_team_cannot_take_the_ticket(): void
    {
        $outsider = User::factory()->admin()->create();
        $outsider->teams()->attach(Team::factory()->create());

        $this->actingAs($outsider)
            ->post(route('desk.tickets.take', $this->newTicket()))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function newTicket(array $attributes = []): Ticket
    {
        return Ticket::factory()
            ->for(User::factory()->create(['name' => 'Carolina Méndez']), 'requester')
            ->create(['team_id' => $this->team->id, ...$attributes]);
    }
}
