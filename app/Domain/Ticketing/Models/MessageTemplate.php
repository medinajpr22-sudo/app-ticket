<?php

namespace App\Domain\Ticketing\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reusable message. Without a team it applies to every team that has no
 * template of its own for the same purpose.
 *
 * @property int $id
 * @property int|null $team_id
 * @property int|null $author_id
 * @property MessageTemplatePurpose $purpose
 * @property string $name
 * @property string $body
 * @property bool $is_active
 */
#[Fillable(['team_id', 'author_id', 'purpose', 'name', 'body', 'is_active'])]
#[UseFactory(MessageTemplateFactory::class)]
class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => MessageTemplatePurpose::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * The active template a team uses for an automatic message: its own if it
     * has one, otherwise the general one. Null when neither exists.
     */
    public static function resolveFor(MessageTemplatePurpose $purpose, Team $team): ?self
    {
        return static::query()
            ->where('purpose', $purpose)
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('team_id', $team->id)->orWhereNull('team_id'))
            ->orderByRaw('team_id is null')
            ->first();
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The person who wrote it; null when the system created it.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
