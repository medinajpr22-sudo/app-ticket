<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketMessageType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a ticket's conversation. The first one is the description.
 *
 * @property int $id
 * @property int $ticket_id
 * @property int|null $author_id
 * @property TicketMessageType $type
 * @property string $body
 */
#[Fillable(['author_id', 'type', 'body'])]
class TicketMessage extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TicketMessageType::class,
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Null for messages the system sent on its own.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Messages the requester is allowed to read: everything but internal notes.
     *
     * @param  Builder<TicketMessage>  $query
     */
    #[Scope]
    protected function visibleToRequester(Builder $query): void
    {
        $query->whereIn('type', TicketMessageType::visibleToRequester());
    }
}
