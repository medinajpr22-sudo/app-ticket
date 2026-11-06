<?php

namespace App\Http\Resources;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\BusinessHour;
use App\Domain\ServiceLevel\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BusinessCalendar
 */
class BusinessCalendarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'timezone' => $this->timezone,
            'is_default' => $this->is_default,
            'hours' => $this->whenLoaded('hours', fn (): array => $this->hours
                ->sortBy(fn (BusinessHour $hour): string => $hour->weekday->value.$hour->opens_at)
                ->map(fn (BusinessHour $hour): array => [
                    'weekday' => $hour->weekday->value,
                    'label' => $hour->weekday->label(),
                    'opens_at' => substr($hour->opens_at, 0, 5),
                    'closes_at' => substr($hour->closes_at, 0, 5),
                ])
                ->values()
                ->all()),
            'holidays' => $this->whenLoaded('holidays', fn (): array => $this->holidays
                ->sortBy('date')
                ->map(fn (Holiday $holiday): array => [
                    'id' => $holiday->id,
                    'name' => $holiday->name,
                    'date' => $holiday->date->toDateString(),
                ])
                ->values()
                ->all()),
            'holidays_count' => $this->whenCounted('holidays'),
        ];
    }
}
