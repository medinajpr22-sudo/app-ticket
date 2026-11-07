<?php

namespace App\Domain\Ticketing\Services;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TemplateVariable;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Str;

/**
 * Fills the {{variables}} of a template with a ticket's data.
 */
class MessageTemplateRenderer
{
    /**
     * @param  User|null  $agent  The person sending the message, when there is one.
     */
    public function render(string $body, Ticket $ticket, ?User $agent = null): string
    {
        $values = $this->valuesFor($ticket, $agent);

        return preg_replace_callback(
            '/\{\{\s*([^{}\s]+)\s*\}\}/',
            fn (array $match): string => $values[$match[1]] ?? $match[0],
            $body,
        );
    }

    /**
     * @return array<string, string>
     */
    private function valuesFor(Ticket $ticket, ?User $agent): array
    {
        $requesterName = $ticket->requester->name;

        return [
            TemplateVariable::Greeting->value => $this->greeting($ticket),
            TemplateVariable::RequesterFirstName->value => Str::before($requesterName, ' '),
            TemplateVariable::RequesterName->value => $requesterName,
            TemplateVariable::AgentName->value => $agent?->name ?? '',
            TemplateVariable::TeamName->value => $ticket->team->name,
            TemplateVariable::CategoryName->value => $ticket->category->name,
            TemplateVariable::TicketNumber->value => (string) $ticket->id,
            TemplateVariable::TicketSubject->value => $ticket->subject,
        ];
    }

    /**
     * Greeting for the time of day where the team works.
     */
    private function greeting(Ticket $ticket): string
    {
        $hour = now($ticket->businessCalendar->timezone)->hour;

        return match (true) {
            $hour < 12 => __('Good morning'),
            $hour < 19 => __('Good afternoon'),
            default => __('Good evening'),
        };
    }
}
