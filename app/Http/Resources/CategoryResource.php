<?php

namespace App\Http\Resources;

use App\Domain\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
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
            'sla_policy_id' => $this->sla_policy_id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon->value,
            'default_ticket_type' => [
                'value' => $this->default_ticket_type->value,
                'label' => $this->default_ticket_type->label(),
            ],
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
            'team' => TeamResource::make($this->whenLoaded('team')),
            'sla_policy' => SlaPolicyResource::make($this->whenLoaded('slaPolicy')),
        ];
    }
}
