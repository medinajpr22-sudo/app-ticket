<?php

namespace App\Domain\ServiceLevel\Models;

use App\Domain\Shared\Concerns\HasSingleDefault;
use Database\Factories\BusinessCalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Working hours and holidays used to count SLA time.
 *
 * @property int $id
 * @property string $name
 * @property string $timezone
 * @property bool $is_default
 */
#[Fillable(['name', 'timezone', 'is_default'])]
#[UseFactory(BusinessCalendarFactory::class)]
class BusinessCalendar extends Model
{
    /** @use HasFactory<BusinessCalendarFactory> */
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
     * @return HasMany<BusinessHour, $this>
     */
    public function hours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }

    /**
     * @return HasMany<Holiday, $this>
     */
    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    /**
     * @return HasMany<SlaPolicy, $this>
     */
    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class);
    }
}
