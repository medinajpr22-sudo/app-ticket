<?php

namespace Database\Factories;

use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => null,
            'author_id' => null,
            'purpose' => MessageTemplatePurpose::CannedResponse,
            'name' => fake()->unique()->sentence(3),
            'body' => 'Hola {{requester.first_name}}, '.fake()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate the kind of message the template is used for.
     */
    public function purpose(MessageTemplatePurpose $purpose): static
    {
        return $this->state(fn (array $attributes): array => [
            'purpose' => $purpose,
        ]);
    }

    /**
     * Indicate that the template is no longer used.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
