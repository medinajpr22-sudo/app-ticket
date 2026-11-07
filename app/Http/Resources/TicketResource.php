<?php

namespace App\Http\Resources;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Services\TicketSlaTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class TicketResource extends JsonResource
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
            'subject' => $this->subject,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'is_open' => $this->status->isOpen(),
            'priority' => ['value' => $this->priority->value, 'label' => $this->priority->label()],
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'channel' => $this->channel->label(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'first_responded_at' => $this->first_responded_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'requester' => UserResource::make($this->whenLoaded('requester')),
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'team' => $this->whenLoaded('team', fn (): array => [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'icon' => $this->team->icon->value,
            ]),
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'icon' => $this->category->icon->value,
            ]),
            'sla' => $this->whenLoaded('businessCalendar', function (): array {
                $sla = app(TicketSlaTracker::class)->evaluate($this->resource);

                return [
                    'state' => ['value' => $sla['state']->value, 'label' => $sla['state']->label()],
                    'target' => $sla['target'],
                    'due_at' => $sla['due_at']->toIso8601String(),
                ];
            }),
            'messages' => TicketMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
