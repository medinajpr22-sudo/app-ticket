<?php

namespace App\Domain\Ticketing\Listeners;

use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Events\TicketTaken;
use App\Domain\Ticketing\Models\MessageTemplate;
use App\Domain\Ticketing\Services\MessageTemplateRenderer;

class SendGreetingMessage
{
    public function __construct(private MessageTemplateRenderer $renderer) {}

    /**
     * Greet the requester in the agent's name as soon as the ticket is taken.
     * It is a real answer from the agent, so it counts as the first response.
     */
    public function handle(TicketTaken $event): void
    {
        $ticket = $event->ticket;
        $template = MessageTemplate::resolveFor(MessageTemplatePurpose::Greeting, $ticket->team);

        if ($template === null) {
            return;
        }

        $ticket->messages()->create([
            'author_id' => $event->agent->id,
            'type' => TicketMessageType::Reply,
            'body' => $this->renderer->render($template->body, $ticket, $event->agent),
        ]);

        $ticket->recordFirstResponse();
    }
}
