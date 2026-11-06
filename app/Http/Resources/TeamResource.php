<?php

namespace App\Http\Resources;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Team
 */
class TeamResource extends JsonResource
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
            'name' => $this->name,
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
            ],
            'description' => $this->description,
            'icon' => $this->icon->value,
            'is_active' => $this->is_active,
            'members' => $this->whenLoaded('members', fn (): array => $this->members
                ->map(fn (User $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'initials' => $member->initials(),
                    'is_lead' => (bool) $member->pivot->getAttribute('is_lead'),
                ])
                ->sortByDesc('is_lead')
                ->values()
                ->all()),
        ];
    }
}
