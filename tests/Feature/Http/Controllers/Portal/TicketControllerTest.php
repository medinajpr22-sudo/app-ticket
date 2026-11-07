<?php

namespace Tests\Feature\Http\Controllers\Portal;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Database\Seeders\MessageTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ServiceLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The default policy gives medium priority 4 working hours for the first
 * response and 24 for the resolution, on office hours 07:00–18:00 in Bogotá.
 */
class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, ServiceLevelSeeder::class, MessageTemplateSeeder::class]);
        $this->travelTo(CarbonImmutable::parse('2026-11-09 09:00', 'America/Bogota'));
    }

    public function test_store_routes_the_ticket_to_the_category_team_with_working_hour_deadlines(): void
    {
        $requester = User::factory()->create(['name' => 'Carolina Méndez']);
        $category = Category::factory()->create();

        $this->actingAs($requester)
            ->post(route('portal.tickets.store'), $this->payload(['category_id' => $category->id]))
            ->assertSessionHasNoErrors();

        $ticket = Ticket::query()->sole();

        $this->assertTrue($ticket->requester()->is($requester));
        $this->assertSame($category->team_id, $ticket->team_id);
        $this->assertSame(TicketStatus::Received, $ticket->status);
        $this->assertSame('2026-11-09 13:00', $ticket->first_response_due_at->setTimezone('America/Bogota')->format('Y-m-d H:i'));
        $this->assertSame('2026-11-11 11:00', $ticket->resolution_due_at->setTimezone('America/Bogota')->format('Y-m-d H:i'));
        $this->assertSame('No puedo conectarme a la VPN desde casa.', $ticket->messages()->oldest('id')->value('body'));
    }

    public function test_store_sends_the_acknowledgement_addressed_to_the_requester(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Carolina Méndez']))
            ->post(route('portal.tickets.store'), $this->payload(['category_id' => Category::factory()->create()->id]))
            ->assertRedirect();

        $acknowledgement = Ticket::query()->sole()->messages()->where('type', TicketMessageType::Automated)->sole();

        $this->assertNull($acknowledgement->author_id);
        $this->assertStringStartsWith('Hola Carolina, recibimos tu solicitud #', $acknowledgement->body);
    }

    public function test_store_rejects_a_hidden_category(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('portal.tickets.store'), $this->payload(['category_id' => Category::factory()->hidden()->create()->id]))
            ->assertSessionHasErrors(['category_id' => 'Elige una categoría disponible.']);

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_store_rejects_the_critical_priority(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('portal.tickets.store'), $this->payload([
                'category_id' => Category::factory()->create()->id,
                'priority' => TicketPriority::Critical->value,
            ]))
            ->assertSessionHasErrors(['priority']);
    }

    public function test_index_lists_only_the_requester_tickets(): void
    {
        $requester = User::factory()->create();
        Ticket::factory()->for($requester, 'requester')->create(['subject' => 'Mi VPN no conecta']);
        Ticket::factory()->create(['subject' => 'Ticket de otra persona']);

        $this->actingAs($requester)
            ->get(route('portal.tickets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Tickets/Index')
                ->has('tickets.data', 1)
                ->where('tickets.data.0.subject', 'Mi VPN no conecta'));
    }

    public function test_requester_cannot_open_someone_elses_ticket(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('portal.tickets.show', Ticket::factory()->create()))
            ->assertForbidden();
    }

    public function test_show_never_includes_internal_notes(): void
    {
        $requester = User::factory()->create();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        $ticket->messages()->create(['author_id' => $requester->id, 'type' => TicketMessageType::Reply, 'body' => 'Descripción']);
        $ticket->messages()->create(['author_id' => null, 'type' => TicketMessageType::InternalNote, 'body' => 'Nota solo para TI']);

        $this->actingAs($requester)
            ->get(route('portal.tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Nota solo para TI')
            ->assertInertia(fn (Assert $page) => $page->has('ticket.messages', 1));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'category_id' => null,
            'subject' => 'No puedo conectarme a la VPN',
            'description' => 'No puedo conectarme a la VPN desde casa.',
            'priority' => TicketPriority::Medium->value,
            ...$overrides,
        ];
    }
}
