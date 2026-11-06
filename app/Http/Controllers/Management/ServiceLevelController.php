<?php

namespace App\Http\Controllers\Management;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessCalendarResource;
use App\Http\Resources\SlaPolicyResource;
use Inertia\Inertia;
use Inertia\Response;

class ServiceLevelController extends Controller
{
    /**
     * Display the SLA policies and the business calendars they count time on.
     */
    public function index(): Response
    {
        $policies = SlaPolicy::query()
            ->with(['businessCalendar', 'targets'])
            ->withCount('categories')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $calendars = BusinessCalendar::query()
            ->with('hours')
            ->withCount('holidays')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return Inertia::render('Management/ServiceLevels/Index', [
            'policies' => SlaPolicyResource::collection($policies),
            'calendars' => BusinessCalendarResource::collection($calendars),
        ]);
    }
}
