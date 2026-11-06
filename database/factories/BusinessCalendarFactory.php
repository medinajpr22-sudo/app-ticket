<?php

namespace Database\Factories;

use App\Domain\ServiceLevel\Enums\Weekday;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessCalendar>
 */
class BusinessCalendarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Horario '.fake()->unique()->word(),
            'timezone' => 'America/Bogota',
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the calendar is used when a policy does not choose one.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    /**
     * Give the calendar office hours from Monday to Friday.
     */
    public function withOfficeHours(string $opensAt = '07:00', string $closesAt = '18:00'): static
    {
        return $this->afterCreating(function (BusinessCalendar $calendar) use ($opensAt, $closesAt): void {
            foreach ([Weekday::Monday, Weekday::Tuesday, Weekday::Wednesday, Weekday::Thursday, Weekday::Friday] as $weekday) {
                $calendar->hours()->create([
                    'weekday' => $weekday,
                    'opens_at' => $opensAt,
                    'closes_at' => $closesAt,
                ]);
            }
        });
    }
}
