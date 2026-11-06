<?php

namespace App\Http\Requests\Management;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Ticketing\Enums\TicketPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSlaPolicyRequest extends FormRequest
{
    /**
     * One year, in minutes. Longer targets are almost certainly a typo.
     */
    private const MAX_TARGET_MINUTES = 525600;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_calendar_id' => ['required', 'integer', Rule::exists(BusinessCalendar::class, 'id')],
            'name' => ['required', 'string', 'max:100', Rule::unique(SlaPolicy::class)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_default' => ['required', 'boolean'],
            'targets' => ['required', 'array', 'size:'.count(TicketPriority::cases())],
            'targets.*.priority' => ['required', 'distinct', Rule::enum(TicketPriority::class)],
            'targets.*.first_response_minutes' => ['required', 'integer', 'min:1', 'max:'.self::MAX_TARGET_MINUTES],
            'targets.*.resolution_minutes' => ['required', 'integer', 'min:1', 'max:'.self::MAX_TARGET_MINUTES, 'gte:targets.*.first_response_minutes'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'targets.*.resolution_minutes.gte' => __('The resolution time cannot be shorter than the first response time.'),
        ];
    }
}
