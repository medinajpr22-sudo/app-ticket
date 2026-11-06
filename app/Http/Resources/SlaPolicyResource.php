<?php

namespace App\Http\Resources;

use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\ServiceLevel\Models\SlaTarget;
use App\Domain\Ticketing\Enums\TicketPriority;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SlaPolicy
 */
class SlaPolicyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_calendar_id' => $this->business_calendar_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_default' => $this->is_default,
            'business_calendar' => BusinessCalendarResource::make($this->whenLoaded('businessCalendar')),
            'targets' => $this->whenLoaded('targets', fn (): array => $this->targets
                ->sortBy(fn (SlaTarget $target): int|false => array_search($target->priority, TicketPriority::cases(), true))
                ->map(fn (SlaTarget $target): array => [
                    'priority' => $target->priority->value,
                    'label' => $target->priority->label(),
                    'first_response_minutes' => $target->first_response_minutes,
                    'resolution_minutes' => $target->resolution_minutes,
                ])
                ->values()
                ->all()),
            'categories_count' => $this->whenCounted('categories'),
        ];
    }
}
