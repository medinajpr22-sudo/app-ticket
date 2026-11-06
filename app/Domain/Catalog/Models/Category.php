<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Organization\Models\Team;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Shared\Enums\Icon;
use App\Domain\Ticketing\Enums\TicketType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What people can ask for. Each category routes its tickets to one team.
 *
 * @property int $id
 * @property int $team_id
 * @property int|null $sla_policy_id
 * @property string $name
 * @property string|null $description
 * @property Icon $icon
 * @property TicketType $default_ticket_type
 * @property bool $is_visible
 * @property int $sort_order
 */
#[Fillable([
    'team_id',
    'sla_policy_id',
    'name',
    'description',
    'icon',
    'default_ticket_type',
    'is_visible',
    'sort_order',
])]
#[UseFactory(CategoryFactory::class)]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'icon' => Icon::class,
            'default_ticket_type' => TicketType::class,
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The policy that overrides the default one, if any.
     *
     * @return BelongsTo<SlaPolicy, $this>
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }
}
