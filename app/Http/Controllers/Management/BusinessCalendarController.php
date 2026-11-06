<?php

namespace App\Http\Controllers\Management;

use App\Domain\ServiceLevel\Actions\SaveBusinessCalendar;
use App\Domain\ServiceLevel\Enums\Weekday;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreBusinessCalendarRequest;
use App\Http\Requests\Management\UpdateBusinessCalendarRequest;
use App\Http\Resources\BusinessCalendarResource;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BusinessCalendarController extends Controller
{
    /**
     * Show the form for creating a calendar.
     */
    public function create(): Response
    {
        return Inertia::render('Management/ServiceLevels/Calendars/Create', $this->formOptions());
    }

    /**
     * Store a newly created calendar.
     */
    public function store(StoreBusinessCalendarRequest $request, SaveBusinessCalendar $saveCalendar): RedirectResponse
    {
        $saveCalendar->handle(new BusinessCalendar, $request->validated());

        return to_route('management.service-levels.index')->with('success', __('Business calendar created.'));
    }

    /**
     * Show the form for editing the calendar.
     */
    public function edit(BusinessCalendar $businessCalendar): Response
    {
        return Inertia::render('Management/ServiceLevels/Calendars/Edit', [
            'calendar' => BusinessCalendarResource::make($businessCalendar->load(['hours', 'holidays'])),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the calendar.
     */
    public function update(UpdateBusinessCalendarRequest $request, BusinessCalendar $businessCalendar, SaveBusinessCalendar $saveCalendar): RedirectResponse
    {
        $saveCalendar->handle($businessCalendar, $request->validated());

        return to_route('management.service-levels.index')->with('success', __('Business calendar updated.'));
    }

    /**
     * Choices shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'weekdays' => array_map(fn (Weekday $weekday): array => [
                'value' => $weekday->value,
                'label' => $weekday->label(),
            ], Weekday::cases()),
            'timezones' => DateTimeZone::listIdentifiers(),
        ];
    }
}
