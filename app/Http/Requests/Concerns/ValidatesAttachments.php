<?php

namespace App\Http\Requests\Concerns;

use App\Domain\Ticketing\Models\TicketAttachment;
use Illuminate\Validation\Rules\File;

/**
 * The same file limits for every form that accepts ticket attachments.
 */
trait ValidatesAttachments
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function attachmentRules(): array
    {
        return [
            'attachments' => ['array', 'max:'.TicketAttachment::MAX_FILES_PER_MESSAGE],
            'attachments.*' => [
                File::types(TicketAttachment::ALLOWED_EXTENSIONS)->max(TicketAttachment::MAX_KILOBYTES),
            ],
        ];
    }
}
