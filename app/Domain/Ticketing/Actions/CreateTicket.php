<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\ServiceLevel\Models\SlaTarget;
use App\Domain\ServiceLevel\Services\BusinessTimeCalculator;
use App\Domain\Ticketing\Enums\TicketChannel;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketCreated;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use LogicException;

class CreateTicket
{
    public function __construct(
        private BusinessTimeCalculator $calculator,
        private StoreMessageAttachments $storeAttachments,
    ) {}

    /**
     * Open a ticket from the portal. The category decides the team, the ticket
     * type and the SLA; the description becomes the first message.
     *
     * @param  array{category_id: int, subject: string, description: string, priority: string, attachments?: list<UploadedFile>}  $attributes
     */
    public function handle(User $requester, array $attributes): Ticket
    {
        return DB::transaction(function () use ($requester, $attributes): Ticket {
            $category = Category::query()->findOrFail($attributes['category_id']);
            $priority = TicketPriority::from($attributes['priority']);

            $policy = $category->effectiveSlaPolicy()->load(['targets', 'businessCalendar.hours', 'businessCalendar.holidays']);
            $target = $policy->targets->first(fn (SlaTarget $target): bool => $target->priority === $priority)
                ?? throw new LogicException("The SLA policy [{$policy->name}] has no target for [{$priority->value}].");
            $calendar = $policy->businessCalendar;
            $now = now();

            $ticket = Ticket::query()->create([
                'requester_id' => $requester->id,
                'creator_id' => $requester->id,
                'team_id' => $category->team_id,
                'category_id' => $category->id,
                'business_calendar_id' => $calendar->id,
                'type' => $category->default_ticket_type,
                'status' => TicketStatus::New,
                'priority' => $priority,
                'channel' => TicketChannel::Portal,
                'subject' => $attributes['subject'],
                'first_response_due_at' => $this->calculator->addMinutes($calendar, $now, $target->first_response_minutes),
                'resolution_due_at' => $this->calculator->addMinutes($calendar, $now, $target->resolution_minutes),
            ]);

            $description = $ticket->messages()->create([
                'author_id' => $requester->id,
                'type' => TicketMessageType::Reply,
                'body' => $attributes['description'],
            ]);

            $this->storeAttachments->handle($description, $attributes['attachments'] ?? []);

            $ticket->statusChanges()->create([
                'user_id' => $requester->id,
                'from_status' => null,
                'to_status' => TicketStatus::New,
            ]);

            $ticket->assignments()->create([
                'team_id' => $category->team_id,
                'assignee_id' => null,
                'user_id' => $requester->id,
            ]);

            TicketCreated::dispatch($ticket);

            return $ticket;
        });
    }
}
