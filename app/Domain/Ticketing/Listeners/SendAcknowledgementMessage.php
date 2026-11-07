<?php

namespace App\Domain\Ticketing\Listeners;

use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Events\TicketCreated;
use App\Domain\Ticketing\Models\MessageTemplate;
use App\Domain\Ticketing\Services\MessageTemplateRenderer;

class SendAcknowledgementMessage
{
    public function __construct(private MessageTemplateRenderer $renderer) {}

    /**
     * Confirm to the requester that the ticket arrived, using the team's template.
     */
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;
        $template = MessageTemplate::resolveFor(MessageTemplatePurpose::Acknowledgement, $ticket->team);

        if ($template === null) {
            return;
        }

        $ticket->messages()->create([
            'author_id' => null,
            'type' => TicketMessageType::Automated,
            'body' => $this->renderer->render($template->body, $ticket),
        ]);
    }
}
