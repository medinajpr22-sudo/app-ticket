<?php

namespace App\Http\Resources;

use App\Domain\Ticketing\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MessageTemplate
 */
class MessageTemplateResource extends JsonResource
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
            'team_id' => $this->team_id,
            'purpose' => [
                'value' => $this->purpose->value,
                'label' => $this->purpose->label(),
            ],
            'is_automatic' => $this->purpose->isAutomatic(),
            'name' => $this->name,
            'body' => $this->body,
            'is_active' => $this->is_active,
            'team' => TeamResource::make($this->whenLoaded('team')),
            'author_name' => $this->whenLoaded('author', fn (): ?string => $this->author?->name),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
