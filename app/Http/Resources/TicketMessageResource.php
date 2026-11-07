<?php

namespace App\Http\Resources;

use App\Domain\Ticketing\Models\TicketAttachment;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketMessage
 */
class TicketMessageResource extends JsonResource
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
            'type' => $this->type->value,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => $this->author ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'initials' => $this->author->initials(),
            ] : null,
            'attachments' => $this->whenLoaded('attachments', fn (): array => $this->attachments
                ->map(fn (TicketAttachment $attachment): array => [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name,
                    'size_bytes' => $attachment->size_bytes,
                    'url' => route('attachments.show', $attachment),
                ])
                ->all()),
        ];
    }
}
