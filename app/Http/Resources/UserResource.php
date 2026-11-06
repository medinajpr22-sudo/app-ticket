<?php

namespace App\Http\Resources;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'email' => $this->email,
            'initials' => $this->initials(),
            'job_title' => $this->job_title,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'role' => RoleResource::make($this->whenLoaded('role')),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'location' => LocationResource::make($this->whenLoaded('location')),
            'teams' => $this->whenLoaded('teams', fn (): array => $this->teams
                ->map(fn (Team $team): array => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'icon' => $team->icon->value,
                    'is_lead' => (bool) $team->pivot->getAttribute('is_lead'),
                ])
                ->all()),
        ];
    }
}
