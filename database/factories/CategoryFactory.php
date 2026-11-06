<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Category;
use App\Domain\Organization\Models\Team;
use App\Domain\Shared\Enums\Icon;
use App\Domain\Ticketing\Enums\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'sla_policy_id' => null,
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(Icon::cases()),
            'default_ticket_type' => fake()->randomElement(TicketType::cases()),
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that people cannot choose the category when creating a ticket.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_visible' => false,
        ]);
    }
}
