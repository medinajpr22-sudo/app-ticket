<?php

namespace App\Domain\ServiceLevel\Models;

use App\Domain\Ticketing\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Business minutes allowed for one priority of a policy.
 *
 * @property int $id
 * @property TicketPriority $priority
 * @property int $first_response_minutes
 * @property int $resolution_minutes
 */
#[Fillable(['priority', 'first_response_minutes', 'resolution_minutes'])]
class SlaTarget extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'first_response_minutes' => 'integer',
            'resolution_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SlaPolicy, $this>
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }
}
