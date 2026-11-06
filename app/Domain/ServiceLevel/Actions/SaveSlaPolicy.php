<?php

namespace App\Domain\ServiceLevel\Actions;

use App\Domain\ServiceLevel\Models\SlaPolicy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveSlaPolicy
{
    /**
     * Create or update a policy together with its target for every priority.
     *
     * @param  array{
     *     business_calendar_id: int,
     *     name: string,
     *     description?: string|null,
     *     is_default: bool,
     *     targets: list<array{priority: string, first_response_minutes: int, resolution_minutes: int}>,
     * }  $attributes
     */
    public function handle(SlaPolicy $policy, array $attributes): SlaPolicy
    {
        return DB::transaction(function () use ($policy, $attributes): SlaPolicy {
            $policy->fill(Arr::except($attributes, ['targets']))->save();
            $policy->demoteOtherDefaults();

            foreach ($attributes['targets'] as $target) {
                $policy->targets()->updateOrCreate(
                    ['priority' => $target['priority']],
                    Arr::only($target, ['first_response_minutes', 'resolution_minutes']),
                );
            }

            return $policy;
        });
    }
}
