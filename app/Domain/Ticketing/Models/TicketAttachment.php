<?php

namespace App\Domain\Ticketing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file sent with a ticket message. Files live on a private disk and are
 * only served through an authorized download.
 *
 * @property int $id
 * @property int $ticket_message_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 */
#[Fillable(['disk', 'path', 'original_name', 'mime_type', 'size_bytes'])]
class TicketAttachment extends Model
{
    public const MAX_FILES_PER_MESSAGE = 5;

    public const MAX_KILOBYTES = 10 * 1024;

    /**
     * Screenshots, documents and spreadsheets. Executables and archives are not accepted.
     */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];

    /**
     * File limits for forms, so they can warn before uploading.
     *
     * @return array{max_files: int, max_kilobytes: int, extensions: list<string>}
     */
    public static function limits(): array
    {
        return [
            'max_files' => self::MAX_FILES_PER_MESSAGE,
            'max_kilobytes' => self::MAX_KILOBYTES,
            'extensions' => self::ALLOWED_EXTENSIONS,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TicketMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }
}
