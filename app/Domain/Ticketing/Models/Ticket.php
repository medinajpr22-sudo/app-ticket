<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\Ticketing\Enums\TicketChannel;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $requester_id
 * @property int $creator_id
 * @property int $team_id
 * @property int $category_id
 * @property int|null $assignee_id
 * @property int $business_calendar_id
 * @property TicketType $type
 * @property TicketStatus $status
 * @property TicketPriority $priority
 * @property TicketChannel $channel
 * @property string $subject
 * @property Carbon $first_response_due_at
 * @property Carbon $resolution_due_at
 * @property Carbon|null $first_responded_at
 * @property Carbon|null $sla_paused_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 * @property int|null $closed_by_id
 * @property Carbon|null $requester_archived_at
 * @property Carbon $created_at
 */
#[Fillable([
    'requester_id',
    'creator_id',
    'team_id',
    'category_id',
    'assignee_id',
    'business_calendar_id',
    'type',
    'status',
    'priority',
    'channel',
    'subject',
    'first_response_due_at',
    'resolution_due_at',
    'first_responded_at',
    'sla_paused_at',
    'resolved_at',
    'closed_at',
    'closed_by_id',
    'requester_archived_at',
])]
#[UseFactory(TicketFactory::class)]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TicketType::class,
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'channel' => TicketChannel::class,
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'requester_archived_at' => 'datetime',
        ];
    }

    /**
     * The person the ticket is for.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Who registered the ticket, usually the requester.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Who closed the ticket: the requester, an agent, or nobody when the system did.
     *
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    /**
     * The calendar the SLA was calculated with when the ticket was created.
     *
     * @return BelongsTo<BusinessCalendar, $this>
     */
    public function businessCalendar(): BelongsTo
    {
        return $this->belongsTo(BusinessCalendar::class);
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * @return HasOne<TicketRating, $this>
     */
    public function rating(): HasOne
    {
        return $this->hasOne(TicketRating::class);
    }

    /**
     * @return HasMany<TicketStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(TicketStatusChange::class);
    }

    /**
     * @return HasMany<TicketAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class);
    }

    /**
     * Stop the first-response clock the first time IT answers.
     */
    public function recordFirstResponse(): void
    {
        if ($this->first_responded_at === null) {
            $this->forceFill(['first_responded_at' => now()])->save();
        }
    }

    /**
     * Tickets that still need work.
     *
     * @param  Builder<Ticket>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::open());
    }

    /**
     * The order of the team inbox: what is running against the clock first,
     * by the deadline that applies now; paused tickets next; finished ones last.
     *
     * @param  Builder<Ticket>  $query
     */
    #[Scope]
    protected function orderByUrgency(Builder $query): void
    {
        $query
            ->orderByRaw(
                'case when status in (?, ?) then 0 when status = ? then 1 else 2 end',
                [TicketStatus::Received->value, TicketStatus::InProgress->value, TicketStatus::WaitingOnRequester->value],
            )
            ->orderByRaw('case when first_responded_at is null then first_response_due_at else resolution_due_at end')
            ->orderBy('id');
    }

    /**
     * Tickets whose subject contains the text, or whose number it is ("#12" or "12").
     *
     * @param  Builder<Ticket>  $query
     */
    #[Scope]
    protected function matching(Builder $query, string $search): void
    {
        $number = ltrim($search, '#');

        $query->where(fn (Builder $query) => $query
            ->whereLike('subject', "%{$search}%")
            ->when(ctype_digit($number), fn (Builder $query) => $query->orWhere('id', (int) $number)));
    }

    /**
     * Tickets IT finished: resolved or closed.
     *
     * @param  Builder<Ticket>  $query
     */
    #[Scope]
    protected function finished(Builder $query): void
    {
        $query->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed]);
    }

    /**
     * Tickets that belong to the teams the user is a member of.
     *
     * @param  Builder<Ticket>  $query
     */
    #[Scope]
    protected function ofTeamsOf(Builder $query, User $user): void
    {
        $query->whereIn('team_id', $user->teams()->select('teams.id'));
    }
}
