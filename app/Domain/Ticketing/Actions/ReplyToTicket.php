<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ReplyToTicket
{
    public function __construct(
        private ChangeTicketStatus $changeStatus,
        private StoreMessageAttachments $storeAttachments,
    ) {}

    /**
     * Add a message to the conversation, with its files.
     *
     * An answer from IT stops the first-response clock. An answer from the
     * requester puts a waiting or resolved ticket back in IT's hands.
     *
     * @param  list<UploadedFile>  $attachments
     */
    public function handle(Ticket $ticket, User $author, string $body, bool $isInternalNote = false, array $attachments = []): TicketMessage
    {
        return DB::transaction(function () use ($ticket, $author, $body, $isInternalNote, $attachments): TicketMessage {
            $message = $ticket->messages()->create([
                'author_id' => $author->id,
                'type' => $isInternalNote ? TicketMessageType::InternalNote : TicketMessageType::Reply,
                'body' => $body,
            ]);

            $this->storeAttachments->handle($message, $attachments);

            if ($isInternalNote) {
                return $message;
            }

            if (! $ticket->requester()->is($author)) {
                $ticket->recordFirstResponse();

                return $message;
            }

            if (in_array($ticket->status, [TicketStatus::WaitingOnRequester, TicketStatus::Resolved], true)) {
                $this->changeStatus->handle(
                    $ticket,
                    $ticket->assignee_id ? TicketStatus::InProgress : TicketStatus::New,
                    $author,
                );
            }

            return $message;
        });
    }
}
