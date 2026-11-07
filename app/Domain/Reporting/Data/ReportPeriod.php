<?php

namespace App\Domain\Reporting\Data;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use Carbon\CarbonImmutable;

/**
 * The days a report covers. People pick whole days in the company's time
 * zone; the database compares in UTC, so both ends are converted.
 */
final readonly class ReportPeriod
{
    public const DEFAULT_DAYS = 30;

    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public string $timezone,
    ) {}

    /**
     * @param  string|null  $from  First day, as Y-m-d; 30 days before $to when empty.
     * @param  string|null  $to  Last day, as Y-m-d; today when empty.
     */
    public static function fromDates(?string $from, ?string $to, ?string $timezone = null): self
    {
        $timezone ??= self::companyTimezone();

        $end = $to === null
            ? CarbonImmutable::now($timezone)->endOfDay()
            : CarbonImmutable::createFromFormat('Y-m-d', $to, $timezone)->endOfDay();

        $start = $from === null
            ? $end->subDays(self::DEFAULT_DAYS - 1)->startOfDay()
            : CarbonImmutable::createFromFormat('Y-m-d', $from, $timezone)->startOfDay();

        return new self($start->utc(), $end->utc(), $timezone);
    }

    /**
     * The time zone of the default business calendar: where the company works.
     */
    public static function companyTimezone(): string
    {
        return BusinessCalendar::findDefault()?->timezone ?? config('app.timezone');
    }

    /**
     * @return array{from: string, to: string}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->setTimezone($this->timezone)->toDateString(),
            'to' => $this->to->setTimezone($this->timezone)->toDateString(),
        ];
    }
}
