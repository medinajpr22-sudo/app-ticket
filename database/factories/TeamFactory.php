<?php

namespace Database\Factories;

use App\Domain\Organization\Enums\TeamType;
use App\Domain\Organization\Models\Team;
use App\Domain\Shared\Enums\Icon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement(TeamType::cases()),
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(Icon::cases()),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the team no longer receives tickets.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
