<?php

namespace App\Domain\ServiceLevel\Actions;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveBusinessCalendar
{
    /**
     * Create or update a calendar, replacing its weekly hours and holidays.
     *
     * @param  array{
     *     name: string,
     *     timezone: string,
     *     is_default: bool,
     *     hours: list<array{weekday: int, is_working: bool, opens_at?: string, closes_at?: string}>,
     *     holidays: list<array{name: string, date: string}>,
     * }  $attributes
     */
    public function handle(BusinessCalendar $calendar, array $attributes): BusinessCalendar
    {
        return DB::transaction(function () use ($calendar, $attributes): BusinessCalendar {
            $calendar->fill(Arr::only($attributes, ['name', 'timezone', 'is_default']))->save();
            $calendar->demoteOtherDefaults();

            $calendar->hours()->delete();
            $calendar->hours()->createMany(
                collect($attributes['hours'])
                    ->where('is_working', true)
                    ->map(fn (array $day): array => Arr::only($day, ['weekday', 'opens_at', 'closes_at']))
                    ->values()
                    ->all(),
            );

            $calendar->holidays()->delete();
            $calendar->holidays()->createMany($attributes['holidays']);

            return $calendar;
        });
    }
}
