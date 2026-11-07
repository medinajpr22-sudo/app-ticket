<?php

namespace App\Domain\ServiceLevel\Services;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\BusinessHour;
use App\Domain\ServiceLevel\Models\Holiday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Generator;
use LogicException;

/**
 * Counts time the way SLAs do: only inside the calendar's working hours,
 * skipping non-working days and holidays, in the calendar's own timezone.
 */
class BusinessTimeCalculator
{
    /**
     * About ten years. A calendar that never opens must not loop forever.
     */
    private const MAX_DAYS_TO_SCAN = 3660;

    /**
     * The moment that comes the given number of working minutes after $from.
     */
    public function addMinutes(BusinessCalendar $calendar, CarbonInterface $from, int $minutes): CarbonImmutable
    {
        $cursor = CarbonImmutable::instance($from)->setTimezone($calendar->timezone);

        if ($minutes <= 0) {
            return $cursor->utc();
        }

        $remaining = $minutes;

        foreach ($this->workingIntervalsFrom($calendar, $cursor) as [$opensAt, $closesAt]) {
            $start = $opensAt->max($cursor);

            if ($start >= $closesAt) {
                continue;
            }

            $available = (int) $start->diffInMinutes($closesAt, true);

            if ($remaining <= $available) {
                return $start->addMinutes($remaining)->utc();
            }

            $remaining -= $available;
        }

        throw new LogicException("The business calendar [{$calendar->name}] has no working hours.");
    }

    /**
     * Working minutes between two moments.
     */
    public function minutesBetween(BusinessCalendar $calendar, CarbonInterface $from, CarbonInterface $to): int
    {
        $from = CarbonImmutable::instance($from)->setTimezone($calendar->timezone);
        $to = CarbonImmutable::instance($to)->setTimezone($calendar->timezone);

        if ($to <= $from) {
            return 0;
        }

        $minutes = 0;

        foreach ($this->workingIntervalsFrom($calendar, $from) as [$opensAt, $closesAt]) {
            if ($opensAt >= $to) {
                break;
            }

            $start = $opensAt->max($from);
            $end = $closesAt->min($to);

            if ($end > $start) {
                $minutes += (int) $start->diffInMinutes($end, true);
            }
        }

        return $minutes;
    }

    /**
     * Working intervals in chronological order, starting on the day of $from.
     *
     * @return Generator<int, array{CarbonImmutable, CarbonImmutable}>
     */
    private function workingIntervalsFrom(BusinessCalendar $calendar, CarbonImmutable $from): Generator
    {
        $hoursByWeekday = $calendar->hours
            ->sortBy('opens_at')
            ->groupBy(fn (BusinessHour $hour): int => $hour->weekday->value);

        $holidays = $calendar->holidays
            ->mapWithKeys(fn (Holiday $holiday): array => [$holiday->date->toDateString() => true]);

        $day = $from->startOfDay();

        for ($scanned = 0; $scanned < self::MAX_DAYS_TO_SCAN; $scanned++, $day = $day->addDay()) {
            if ($holidays->has($day->toDateString())) {
                continue;
            }

            foreach ($hoursByWeekday->get($day->dayOfWeekIso, []) as $hour) {
                yield [
                    $day->setTimeFromTimeString($hour->opens_at),
                    $day->setTimeFromTimeString($hour->closes_at),
                ];
            }
        }
    }
}
