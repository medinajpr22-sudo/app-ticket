<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StoreMessageAttachments
{
    private const DISK = 'local';

    /**
     * Save the uploaded files on the private disk and link them to the message.
     * If anything fails, the files already written are removed again.
     *
     * @param  list<UploadedFile>  $files
     */
    public function handle(TicketMessage $message, array $files): void
    {
        $storedPaths = [];

        try {
            foreach ($files as $file) {
                $path = $file->store("tickets/{$message->ticket_id}", self::DISK);
                $storedPaths[] = $path;

                $message->attachments()->create([
                    'disk' => self::DISK,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                    'size_bytes' => $file->getSize(),
                ]);
            }
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete($storedPaths);

            throw $exception;
        }
    }
}
