<?php

namespace App\Domain\ServiceLevel\Models;

use App\Domain\Catalog\Models\Category;
use App\Domain\Shared\Concerns\HasSingleDefault;
use Database\Factories\SlaPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Response and resolution times per priority, counted on a business calendar.
 *
 * @property int $id
 * @property int $business_calendar_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_default
 */
#[Fillable(['business_calendar_id', 'name', 'description', 'is_default'])]
#[UseFactory(SlaPolicyFactory::class)]
class SlaPolicy extends Model
{
    /** @use HasFactory<SlaPolicyFactory> */
    use HasFactory, HasSingleDefault;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<BusinessCalendar, $this>
     */
    public function businessCalendar(): BelongsTo
    {
        return $this->belongsTo(BusinessCalendar::class);
    }

    /**
     * @return HasMany<SlaTarget, $this>
     */
    public function targets(): HasMany
    {
        return $this->hasMany(SlaTarget::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
