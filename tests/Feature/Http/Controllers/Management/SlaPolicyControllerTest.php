<?php

namespace Tests\Feature\Http\Controllers\Management;

use App\Domain\Identity\Models\User;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Ticketing\Enums\TicketPriority;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaPolicyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_without_sla_management_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('management.service-levels.policies.create'))
            ->assertForbidden();
    }

    public function test_store_creates_a_policy_with_a_target_for_each_priority(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.service-levels.policies.store'), $this->payload())
            ->assertRedirect(route('management.service-levels.index'))
            ->assertSessionHas('success', 'Política de SLA creada.');

        $policy = SlaPolicy::query()->where('name', 'Sistemas clínicos')->firstOrFail();

        $this->assertCount(count(TicketPriority::cases()), $policy->targets);
        $this->assertDatabaseHas('sla_targets', [
            'sla_policy_id' => $policy->id,
            'priority' => TicketPriority::Critical->value,
            'first_response_minutes' => 15,
            'resolution_minutes' => 120,
        ]);
    }

    public function test_store_rejects_a_resolution_shorter_than_the_first_response(): void
    {
        $payload = $this->payload();
        $payload['targets'][1]['resolution_minutes'] = 10;

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.service-levels.policies.store'), $payload)
            ->assertSessionHasErrors([
                'targets.1.resolution_minutes' => 'La resolución no puede tomar menos tiempo que la primera respuesta.',
            ]);

        $this->assertDatabaseCount('sla_policies', 0);
    }

    public function test_marking_a_policy_as_default_removes_the_previous_default(): void
    {
        $previousDefault = SlaPolicy::factory()->default()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.service-levels.policies.store'), $this->payload(['is_default' => true]))
            ->assertRedirect(route('management.service-levels.index'));

        $this->assertFalse($previousDefault->refresh()->is_default);
        $this->assertSame('Sistemas clínicos', SlaPolicy::findDefault()?->name);
    }

    public function test_update_rejects_removing_the_default_flag_from_the_default_policy(): void
    {
        $policy = SlaPolicy::factory()->default()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.service-levels.policies.update', $policy), $this->payload([
                'business_calendar_id' => $policy->business_calendar_id,
                'is_default' => false,
            ]))
            ->assertSessionHasErrors([
                'is_default' => 'Siempre debe haber una política predeterminada. Marca otra como predeterminada en su lugar.',
            ]);

        $this->assertTrue($policy->refresh()->is_default);
    }

    public function test_update_changes_the_existing_targets(): void
    {
        $policy = SlaPolicy::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.service-levels.policies.update', $policy), $this->payload([
                'business_calendar_id' => $policy->business_calendar_id,
            ]))
            ->assertRedirect(route('management.service-levels.index'));

        $this->assertCount(count(TicketPriority::cases()), $policy->targets()->get());
        $this->assertSame(15, $policy->targets()->where('priority', TicketPriority::Critical)->value('first_response_minutes'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'business_calendar_id' => $overrides['business_calendar_id'] ?? BusinessCalendar::factory()->create()->id,
            'name' => 'Sistemas clínicos',
            'description' => 'Incidentes que pueden afectar la atención de pacientes.',
            'is_default' => false,
            'targets' => [
                ['priority' => TicketPriority::Critical->value, 'first_response_minutes' => 15, 'resolution_minutes' => 120],
                ['priority' => TicketPriority::High->value, 'first_response_minutes' => 30, 'resolution_minutes' => 240],
                ['priority' => TicketPriority::Medium->value, 'first_response_minutes' => 120, 'resolution_minutes' => 720],
                ['priority' => TicketPriority::Low->value, 'first_response_minutes' => 240, 'resolution_minutes' => 1440],
            ],
            ...$overrides,
        ];
    }
}
