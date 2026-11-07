<?php

namespace App\Domain\Ticketing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How the requester rated the attention, from 1 to 5. One per ticket.
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $score
 * @property string|null $comment
 */
#[Fillable(['score', 'comment'])]
class TicketRating extends Model
{
    public const MIN_SCORE = 1;

    public const MAX_SCORE = 5;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
