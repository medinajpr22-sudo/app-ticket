<?php

namespace App\Http\Controllers;

use App\Domain\Ticketing\Models\TicketAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    /**
     * Download a ticket file with its original name, after checking who asks.
     */
    public function __invoke(TicketAttachment $attachment): StreamedResponse
    {
        Gate::authorize('download', $attachment);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
