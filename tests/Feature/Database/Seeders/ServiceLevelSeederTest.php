<?php

namespace Tests\Feature\Database\Seeders;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Ticketing\Enums\TicketPriority;
use Database\Seeders\ServiceLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceLevelSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_default_calendar_and_policy_with_every_priority(): void
    {
        $this->seed(ServiceLevelSeeder::class);

        $policy = SlaPolicy::findDefault();

        $this->assertNotNull($policy);
        $this->assertTrue($policy->businessCalendar->is_default);
        $this->assertCount(count(TicketPriority::cases()), $policy->targets);
    }

    public function test_running_again_keeps_the_existing_defaults(): void
    {
        $this->seed(ServiceLevelSeeder::class);
        SlaPolicy::findDefault()?->update(['name' => 'Renombrada']);

        $this->seed(ServiceLevelSeeder::class);

        $this->assertDatabaseCount('business_calendars', 1);
        $this->assertDatabaseCount('sla_policies', 1);
        $this->assertSame('Renombrada', SlaPolicy::findDefault()?->name);
        $this->assertSame(1, BusinessCalendar::query()->where('is_default', true)->count());
    }
}
