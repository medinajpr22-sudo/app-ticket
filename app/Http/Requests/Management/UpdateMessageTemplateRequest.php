<?php

namespace App\Http\Requests\Management;

use App\Domain\Ticketing\Models\MessageTemplate;

class UpdateMessageTemplateRequest extends StoreMessageTemplateRequest
{
    protected function template(): ?MessageTemplate
    {
        return $this->route('messageTemplate');
    }
}
