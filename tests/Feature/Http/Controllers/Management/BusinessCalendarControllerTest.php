<?php

namespace Tests\Feature\Http\Controllers\Management;

use App\Domain\Identity\Models\User;
use App\Domain\ServiceLevel\Enums\Weekday;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_keeps_only_the_working_days_and_the_holidays(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.service-levels.calendars.store'), $this->payload())
            ->assertRedirect(route('management.service-levels.index'))
            ->assertSessionHas('success', 'Horario creado.');

        $calendar = BusinessCalendar::query()->where('name', 'Horario extendido')->firstOrFail();

        $this->assertSame(
            [Weekday::Monday, Weekday::Tuesday, Weekday::Wednesday, Weekday::Thursday, Weekday::Friday, Weekday::Saturday],
            $calendar->hours()->orderBy('weekday')->get()->pluck('weekday')->all(),
        );
        $this->assertDatabaseHas('holidays', ['business_calendar_id' => $calendar->id, 'name' => 'Navidad']);
    }

    public function test_store_rejects_a_closing_time_before_the_opening_time(): void
    {
        $payload = $this->payload();
        $payload['hours'][0]['closes_at'] = '06:00';

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.service-levels.calendars.store'), $payload)
            ->assertSessionHasErrors(['hours.0.closes_at' => 'La hora de cierre debe ser posterior a la de apertura.']);
    }

    public function test_store_requires_at_least_one_working_day(): void
    {
        $payload = $this->payload();
        $payload['hours'] = array_map(fn (array $day): array => [...$day, 'is_working' => false], $payload['hours']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('management.service-levels.calendars.store'), $payload)
            ->assertSessionHasErrors(['hours' => 'Elige al menos un día laborable.']);

        $this->assertDatabaseCount('business_calendars', 0);
    }

    public function test_update_replaces_hours_and_holidays(): void
    {
        $calendar = BusinessCalendar::factory()->withOfficeHours()->create();
        $calendar->holidays()->create(['name' => 'Día del Trabajo', 'date' => '2027-05-01']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.service-levels.calendars.update', $calendar), $this->payload())
            ->assertRedirect(route('management.service-levels.index'));

        $this->assertSame(6, $calendar->hours()->count());
        $this->assertSame(['Navidad'], $calendar->holidays()->pluck('name')->all());
    }

    public function test_update_rejects_removing_the_default_flag_from_the_default_calendar(): void
    {
        $calendar = BusinessCalendar::factory()->default()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('management.service-levels.calendars.update', $calendar), $this->payload(['is_default' => false]))
            ->assertSessionHasErrors([
                'is_default' => 'Siempre debe haber un horario predeterminado. Marca otro como predeterminado en su lugar.',
            ]);

        $this->assertTrue($calendar->refresh()->is_default);
    }

    /**
     * Monday to Saturday from 07:00 to 18:00, with Sunday off.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Horario extendido',
            'timezone' => 'America/Bogota',
            'is_default' => false,
            'hours' => array_map(fn (Weekday $weekday): array => [
                'weekday' => $weekday->value,
                'is_working' => $weekday !== Weekday::Sunday,
                'opens_at' => '07:00',
                'closes_at' => '18:00',
            ], Weekday::cases()),
            'holidays' => [
                ['name' => 'Navidad', 'date' => '2026-12-25'],
            ],
            ...$overrides,
        ];
    }
}
