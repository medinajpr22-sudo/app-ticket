<?php

namespace Database\Seeders;

use App\Domain\ServiceLevel\Enums\Weekday;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Ticketing\Enums\TicketPriority;
use Illuminate\Database\Seeder;

/**
 * Reference data required in every environment: the default business calendar
 * and SLA policy. Safe to run repeatedly; existing defaults are left untouched.
 */
class ServiceLevelSeeder extends Seeder
{
    /**
     * Business minutes for [first response, resolution] per priority.
     *
     * @var array<string, array{int, int}>
     */
    private const DEFAULT_TARGETS = [
        'critical' => [30, 240],
        'high' => [60, 480],
        'medium' => [240, 1440],
        'low' => [480, 2400],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $calendar = BusinessCalendar::query()->where('is_default', true)->first()
            ?? $this->createDefaultCalendar();

        if (SlaPolicy::query()->where('is_default', true)->doesntExist()) {
            $this->createDefaultPolicy($calendar);
        }
    }

    private function createDefaultCalendar(): BusinessCalendar
    {
        $calendar = BusinessCalendar::query()->create([
            'name' => 'Horario de oficina',
            'timezone' => 'America/Bogota',
            'is_default' => true,
        ]);

        foreach ([Weekday::Monday, Weekday::Tuesday, Weekday::Wednesday, Weekday::Thursday, Weekday::Friday] as $weekday) {
            $calendar->hours()->create([
                'weekday' => $weekday,
                'opens_at' => '07:00',
                'closes_at' => '18:00',
            ]);
        }

        return $calendar;
    }

    private function createDefaultPolicy(BusinessCalendar $calendar): void
    {
        $policy = SlaPolicy::query()->create([
            'business_calendar_id' => $calendar->id,
            'name' => 'Estándar',
            'description' => 'Tiempos de atención para la mayoría de solicitudes.',
            'is_default' => true,
        ]);

        foreach (TicketPriority::cases() as $priority) {
            [$firstResponse, $resolution] = self::DEFAULT_TARGETS[$priority->value];

            $policy->targets()->create([
                'priority' => $priority,
                'first_response_minutes' => $firstResponse,
                'resolution_minutes' => $resolution,
            ]);
        }
    }
}
