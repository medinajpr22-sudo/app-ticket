<?php

namespace App\Http\Controllers\Management;

use App\Domain\ServiceLevel\Actions\SaveSlaPolicy;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreSlaPolicyRequest;
use App\Http\Requests\Management\UpdateSlaPolicyRequest;
use App\Http\Resources\BusinessCalendarResource;
use App\Http\Resources\SlaPolicyResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SlaPolicyController extends Controller
{
    /**
     * Show the form for creating a policy.
     */
    public function create(): Response
    {
        $defaultPolicy = SlaPolicy::findDefault()?->load('targets');

        return Inertia::render('Management/ServiceLevels/Policies/Create', [
            'defaultPolicy' => $defaultPolicy ? SlaPolicyResource::make($defaultPolicy) : null,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created policy.
     */
    public function store(StoreSlaPolicyRequest $request, SaveSlaPolicy $savePolicy): RedirectResponse
    {
        $savePolicy->handle(new SlaPolicy, $request->validated());

        return to_route('management.service-levels.index')->with('success', __('SLA policy created.'));
    }

    /**
     * Show the form for editing the policy.
     */
    public function edit(SlaPolicy $slaPolicy): Response
    {
        return Inertia::render('Management/ServiceLevels/Policies/Edit', [
            'policy' => SlaPolicyResource::make($slaPolicy->load('targets')),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the policy.
     */
    public function update(UpdateSlaPolicyRequest $request, SlaPolicy $slaPolicy, SaveSlaPolicy $savePolicy): RedirectResponse
    {
        $savePolicy->handle($slaPolicy, $request->validated());

        return to_route('management.service-levels.index')->with('success', __('SLA policy updated.'));
    }

    /**
     * Choices shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'calendars' => BusinessCalendarResource::collection(
                BusinessCalendar::query()->orderByDesc('is_default')->orderBy('name')->get(),
            ),
            'priorities' => TicketPriority::options(),
        ];
    }
}
