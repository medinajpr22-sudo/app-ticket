<?php

namespace App\Domain\Organization\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

enum TeamType: string
{
    use HasTranslatedLabel;

    case GeneralSupport = 'general_support';
    case ApplicationSupport = 'application_support';

    public function description(): string
    {
        return __('enums.team_type_description.'.$this->name);
    }
}
