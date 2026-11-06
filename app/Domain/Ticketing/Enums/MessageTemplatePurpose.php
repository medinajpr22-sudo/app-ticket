<?php

namespace App\Domain\Ticketing\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

enum MessageTemplatePurpose: string
{
    use HasTranslatedLabel;

    case Greeting = 'greeting';
    case Acknowledgement = 'acknowledgement';
    case AutoClose = 'auto_close';
    case CannedResponse = 'canned_response';

    public function description(): string
    {
        return __('enums.message_template_purpose_description.'.$this->name);
    }

    /**
     * Automatic messages are sent by the system, so each team can only
     * have one active template of each kind. Canned responses can be many.
     */
    public function isAutomatic(): bool
    {
        return $this !== self::CannedResponse;
    }

    /**
     * @return list<self>
     */
    public static function automatic(): array
    {
        return array_values(array_filter(self::cases(), fn (self $purpose): bool => $purpose->isAutomatic()));
    }
}
