<?php

namespace App\Http\Controllers;

use App\Domain\Ticketing\Models\TicketAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentPreviewController extends Controller
{
    /**
     * Show an image of the conversation inside the page, after checking who
     * asks. Only real pictures are served inline; any other file is downloaded.
     */
    public function __invoke(TicketAttachment $attachment): StreamedResponse
    {
        Gate::authorize('download', $attachment);

        abort_unless($attachment->isPreviewableImage(), 404);

        return Storage::disk($attachment->disk)->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
