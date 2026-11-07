<?php

namespace App\Http\Requests\Concerns;

use App\Domain\Reporting\Data\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

/**
 * The "from" and "to" days of a report. A report covers at most a year, so
 * one request never has to read the whole history.
 */
trait ValidatesReportPeriod
{
    private const MAX_PERIOD_DAYS = 366;

    /**
     * @return array<string, array<int, string>>
     */
    protected function periodRules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function period(): ReportPeriod
    {
        return ReportPeriod::fromDates($this->validated('from'), $this->validated('to'));
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['from', 'to']) || ! $this->filled(['from', 'to'])) {
                    return;
                }

                $days = CarbonImmutable::parse($this->string('from'))->diffInDays(CarbonImmutable::parse($this->string('to')));

                if ($days >= self::MAX_PERIOD_DAYS) {
                    $validator->errors()->add('from', __('Choose a period of one year at most.'));
                }
            },
        ];
    }
}
