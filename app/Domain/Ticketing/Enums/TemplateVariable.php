<?php

namespace App\Domain\Ticketing\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

/**
 * Placeholders that message templates can use, written as {{requester.first_name}}.
 */
enum TemplateVariable: string
{
    use HasTranslatedLabel;

    case Greeting = 'greeting';
    case RequesterFirstName = 'requester.first_name';
    case RequesterName = 'requester.name';
    case AgentName = 'agent.name';
    case TeamName = 'team.name';
    case CategoryName = 'category.name';
    case TicketNumber = 'ticket.number';
    case TicketSubject = 'ticket.subject';

    private const TOKEN_PATTERN = '/\{\{\s*([^{}\s]+)\s*\}\}/';

    public function token(): string
    {
        return '{{'.$this->value.'}}';
    }

    /**
     * Example value used to preview a template before any ticket exists.
     */
    public function sample(): string
    {
        return __('enums.template_variable_sample.'.$this->name);
    }

    /**
     * Whether the value is known at the moment that kind of message is sent.
     * The acknowledgement goes out before any agent has taken the ticket.
     */
    public function isAvailableFor(MessageTemplatePurpose $purpose): bool
    {
        return ! ($this === self::AgentName && $purpose === MessageTemplatePurpose::Acknowledgement);
    }

    /**
     * @return list<self>
     */
    public static function availableFor(MessageTemplatePurpose $purpose): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $variable): bool => $variable->isAvailableFor($purpose),
        ));
    }

    /**
     * Variable names written in a template body, e.g. ["requester.first_name"].
     *
     * @return list<string>
     */
    public static function namesUsedIn(string $body): array
    {
        preg_match_all(self::TOKEN_PATTERN, $body, $matches);

        return array_values(array_unique($matches[1]));
    }
}
