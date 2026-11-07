<?php

namespace Tests\Feature\Http\Controllers\Reports;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Team;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Tickets use office hours Monday to Friday, 07:00–18:00 in Bogotá.
 */
class OverviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private BusinessCalendar $calendar;

    private Team $network;

    private Team $applications;

    private Department $billing;

    private Department $nursing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-13 17:00', 'America/Bogota'));

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->calendar = BusinessCalendar::factory()->default()->withOfficeHours()->create();
        $this->network = Team::factory()->create(['name' => 'Redes']);
        $this->applications = Team::factory()->create(['name' => 'Aplicaciones']);
        $this->billing = Department::factory()->create(['name' => 'Facturación']);
        $this->nursing = Department::factory()->create(['name' => 'Enfermería']);
    }

    public function test_the_super_admin_lands_on_the_statistics(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('reports.overview'));
    }

    public function test_the_statistics_count_tickets_by_area_category_and_team(): void
    {
        $vpn = Category::factory()->create(['team_id' => $this->network->id, 'name' => 'VPN']);
        $sap = Category::factory()->create(['team_id' => $this->applications->id, 'name' => 'SAP']);

        $this->ticket($this->billing, $vpn);
        $this->ticket($this->billing, $vpn);
        $this->ticket($this->nursing, $sap);
        $this->ticket(null, $sap);

        $this->actingAs($this->superAdmin)
            ->get(route('reports.overview'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Overview')
                ->where('summary.total', 4)
                ->where('summary.received', 4)
                ->where('byDepartment', [
                    ['id' => $this->billing->id, 'label' => 'Facturación', 'total' => 2],
                    ['id' => $this->nursing->id, 'label' => 'Enfermería', 'total' => 1],
                    ['id' => null, 'label' => 'Sin área', 'total' => 1],
                ])
                ->where('byCategory.0', ['id' => $sap->id, 'label' => 'SAP', 'total' => 2])
                ->where('byTeam', [
                    ['id' => $this->applications->id, 'label' => 'Aplicaciones', 'total' => 2],
                    ['id' => $this->network->id, 'label' => 'Redes', 'total' => 2],
                ]));
    }

    public function test_the_statistics_measure_sla_speed_and_ratings_per_team_and_agent(): void
    {
        $agent = User::factory()->admin()->create(['name' => 'Ana Torres']);
        $agent->teams()->attach($this->network);
        $category = Category::factory()->create(['team_id' => $this->network->id]);
        $monday = CarbonImmutable::parse('2026-11-09 08:00', 'America/Bogota');

        $ticket = $this->ticket($this->billing, $category, $monday, [
            'assignee_id' => $agent->id,
            'status' => TicketStatus::Closed,
            'first_responded_at' => $monday->addMinutes(20)->utc(),
            'resolved_at' => $monday->addHours(3)->utc(),
        ]);
        $ticket->rating()->create(['score' => 4]);
        $this->ticket($this->billing, $category, $monday, ['assignee_id' => $agent->id, 'status' => TicketStatus::InProgress]);

        $this->actingAs($this->superAdmin)
            ->get(route('reports.overview'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.resolved', 1)
                ->where('summary.in_progress', 1)
                ->where('summary.resolution_compliance', 100)
                ->where('summary.average_first_response_minutes', 20)
                ->where('summary.average_resolution_minutes', 180)
                ->where('summary.average_rating', 4)
                ->where('teamPerformance.0.name', 'Redes')
                ->where('teamPerformance.0.metrics.total', 2)
                ->where('agentPerformance.0.name', 'Ana Torres')
                ->where('agentPerformance.0.teams', ['Redes'])
                ->where('agentPerformance.0.open_now', 1)
                ->where('agentPerformance.0.metrics.resolved', 1));
    }

    public function test_the_statistics_filter_by_period_area_team_and_status(): void
    {
        $category = Category::factory()->create(['team_id' => $this->network->id]);
        $other = Category::factory()->create(['team_id' => $this->applications->id]);

        $this->ticket($this->billing, $category, CarbonImmutable::parse('2026-11-10 09:00', 'America/Bogota'));
        $this->ticket($this->billing, $category, CarbonImmutable::parse('2026-09-10 09:00', 'America/Bogota'));
        $this->ticket($this->nursing, $category, CarbonImmutable::parse('2026-11-10 09:00', 'America/Bogota'));
        $this->ticket($this->billing, $other, CarbonImmutable::parse('2026-11-10 09:00', 'America/Bogota'));
        $this->ticket($this->billing, $category, CarbonImmutable::parse('2026-11-11 09:00', 'America/Bogota'), ['status' => TicketStatus::Closed]);

        $this->actingAs($this->superAdmin)
            ->get(route('reports.overview', [
                'from' => '2026-11-01',
                'to' => '2026-11-13',
                'department' => $this->billing->id,
                'team' => $this->network->id,
                'status' => 'open',
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.total', 1)
                ->where('filters', [
                    'from' => '2026-11-01',
                    'to' => '2026-11-13',
                    'department' => (string) $this->billing->id,
                    'team' => (string) $this->network->id,
                    'status' => 'open',
                ]));
    }

    public function test_the_status_filter_rejects_the_retired_statuses(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('reports.overview', ['status' => TicketStatus::Cancelled->value]))
            ->assertSessionHasErrors(['status']);
    }

    public function test_leads_and_agents_do_not_see_the_whole_help_desk(): void
    {
        $lead = User::factory()->admin()->create();
        $lead->teams()->attach($this->network, ['is_lead' => true]);

        $this->actingAs($lead)->get(route('reports.overview'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('reports.overview'))->assertForbidden();
    }

    public function test_agents_land_on_their_inbox_and_everyone_else_on_their_tickets(): void
    {
        $agent = User::factory()->admin()->create();
        $agent->teams()->attach($this->network);

        $this->actingAs($agent)->get(route('dashboard'))->assertRedirect(route('desk.tickets.index'));
        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertRedirect(route('portal.tickets.index'));
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertRedirect(route('portal.tickets.index'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function ticket(?Department $department, Category $category, ?CarbonImmutable $createdAt = null, array $attributes = []): Ticket
    {
        $createdAt ??= CarbonImmutable::parse('2026-11-12 09:00', 'America/Bogota');

        $ticket = Ticket::factory()
            ->for(User::factory()->create(['department_id' => $department?->id]), 'requester')
            ->create([
                'category_id' => $category->id,
                'team_id' => $category->team_id,
                'business_calendar_id' => $this->calendar->id,
                'first_response_due_at' => $createdAt->addHour()->utc(),
                'resolution_due_at' => $createdAt->addHours(4)->utc(),
                ...$attributes,
            ]);

        $ticket->forceFill(['created_at' => $createdAt->utc()])->save();

        return $ticket;
    }
}
