<?php

namespace Database\Factories;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Ticketing\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaPolicy>
 */
class SlaPolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_calendar_id' => BusinessCalendar::factory(),
            'name' => 'Política '.fake()->unique()->word(),
            'description' => fake()->sentence(),
            'is_default' => false,
        ];
    }

    /**
     * Every policy defines a target for each priority.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (SlaPolicy $policy): void {
            $minutes = [
                TicketPriority::Critical->value => [30, 240],
                TicketPriority::High->value => [60, 480],
                TicketPriority::Medium->value => [240, 1440],
                TicketPriority::Low->value => [480, 2880],
            ];

            foreach (TicketPriority::cases() as $priority) {
                $policy->targets()->create([
                    'priority' => $priority,
                    'first_response_minutes' => $minutes[$priority->value][0],
                    'resolution_minutes' => $minutes[$priority->value][1],
                ]);
            }
        });
    }

    /**
     * Indicate that categories without their own policy use this one.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }
}
