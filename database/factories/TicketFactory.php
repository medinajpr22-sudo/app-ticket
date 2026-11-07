<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\Ticketing\Enums\TicketChannel;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'creator_id' => fn (array $attributes): int => $attributes['requester_id'],
            'category_id' => Category::factory(),
            'team_id' => fn (array $attributes): int => Category::query()->findOrFail($attributes['category_id'])->team_id,
            'assignee_id' => null,
            'business_calendar_id' => fn (): int => BusinessCalendar::factory()->withOfficeHours()->create()->id,
            'type' => TicketType::Incident,
            'status' => TicketStatus::New,
            'priority' => TicketPriority::Medium,
            'channel' => TicketChannel::Portal,
            'subject' => fake()->sentence(6),
            'first_response_due_at' => now()->addHours(4),
            'resolution_due_at' => now()->addDay(),
        ];
    }

    /**
     * Indicate that an agent took the ticket and is working on it.
     */
    public function takenBy(User $agent): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignee_id' => $agent->id,
            'status' => TicketStatus::InProgress,
        ]);
    }

    /**
     * Indicate that IT is waiting for the requester's answer.
     */
    public function waitingOnRequester(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::WaitingOnRequester,
            'sla_paused_at' => now(),
        ]);
    }

    /**
     * Indicate that the ticket was solved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }
}
