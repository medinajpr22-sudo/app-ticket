<?php

namespace App\Domain\ServiceLevel\Models;

use App\Domain\ServiceLevel\Enums\Weekday;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One working interval of a weekday, e.g. Monday from 07:00 to 18:00.
 *
 * @property int $id
 * @property Weekday $weekday
 * @property string $opens_at
 * @property string $closes_at
 */
#[Fillable(['weekday', 'opens_at', 'closes_at'])]
class BusinessHour extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
        ];
    }

    /**
     * @return BelongsTo<BusinessCalendar, $this>
     */
    public function businessCalendar(): BelongsTo
    {
        return $this->belongsTo(BusinessCalendar::class);
    }
}
