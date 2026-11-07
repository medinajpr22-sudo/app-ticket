<?php

namespace Tests\Feature\Domain\ServiceLevel\Services;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Services\BusinessTimeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Office hours Monday to Friday, 07:00–18:00 in Bogotá (UTC-5, no daylight saving).
 * Friday 2026-12-25 is a holiday.
 */
class BusinessTimeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private BusinessCalendar $calendar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calendar = BusinessCalendar::factory()->withOfficeHours()->create(['timezone' => 'America/Bogota']);
        $this->calendar->holidays()->create(['name' => 'Navidad', 'date' => '2026-12-25']);
        $this->calendar->load(['hours', 'holidays']);
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function additions(): array
    {
        return [
            'within the same day' => ['2026-11-09 09:00', 60, '2026-11-09 10:00'],
            'carries over to the next morning' => ['2026-11-09 17:00', 120, '2026-11-10 08:00'],
            'starts before opening time' => ['2026-11-09 05:00', 30, '2026-11-09 07:30'],
            'skips the weekend' => ['2026-11-13 17:30', 60, '2026-11-16 07:30'],
            'skips a holiday and the weekend' => ['2026-12-24 17:00', 120, '2026-12-28 08:00'],
            'zero minutes keeps the moment' => ['2026-11-09 20:00', 0, '2026-11-09 20:00'],
        ];
    }

    #[DataProvider('additions')]
    public function test_adds_only_working_minutes(string $from, int $minutes, string $expected): void
    {
        $due = app(BusinessTimeCalculator::class)->addMinutes($this->calendar, $this->bogota($from), $minutes);

        $this->assertSame($expected, $due->setTimezone('America/Bogota')->format('Y-m-d H:i'));
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function intervals(): array
    {
        return [
            'within the same day' => ['2026-11-09 09:00', '2026-11-09 11:30', 150],
            'overnight only counts office hours' => ['2026-11-09 17:00', '2026-11-10 08:00', 120],
            'a whole weekend counts nothing' => ['2026-11-14 08:00', '2026-11-15 17:00', 0],
            'a holiday counts nothing' => ['2026-12-25 07:00', '2026-12-25 18:00', 0],
            'end before start' => ['2026-11-10 08:00', '2026-11-09 08:00', 0],
        ];
    }

    #[DataProvider('intervals')]
    public function test_counts_working_minutes_between_two_moments(string $from, string $to, int $expected): void
    {
        $minutes = app(BusinessTimeCalculator::class)->minutesBetween($this->calendar, $this->bogota($from), $this->bogota($to));

        $this->assertSame($expected, $minutes);
    }

    public function test_due_dates_are_returned_in_utc(): void
    {
        $due = app(BusinessTimeCalculator::class)->addMinutes($this->calendar, $this->bogota('2026-11-09 09:00'), 60);

        $this->assertSame('UTC', $due->getTimezone()->getName());
        $this->assertSame('2026-11-09 15:00', $due->format('Y-m-d H:i'));
    }

    private function bogota(string $moment): CarbonImmutable
    {
        return CarbonImmutable::parse($moment, 'America/Bogota');
    }
}
