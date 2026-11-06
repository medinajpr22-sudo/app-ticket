<?php

namespace App\Http\Requests\Management;

use App\Domain\ServiceLevel\Enums\Weekday;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBusinessCalendarRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Every weekday is sent, worked or not, so errors line up with the rows on screen.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(BusinessCalendar::class)],
            'timezone' => ['required', 'timezone:all'],
            'is_default' => ['required', 'boolean'],
            'hours' => ['required', 'array', 'size:'.count(Weekday::cases())],
            'hours.*.weekday' => ['required', 'distinct', Rule::enum(Weekday::class)],
            'hours.*.is_working' => ['required', 'boolean'],
            'hours.*.opens_at' => ['exclude_unless:hours.*.is_working,true', 'required', 'date_format:H:i'],
            'hours.*.closes_at' => ['exclude_unless:hours.*.is_working,true', 'required', 'date_format:H:i', 'after:hours.*.opens_at'],
            'holidays' => ['present', 'array'],
            'holidays.*.name' => ['required', 'string', 'max:100'],
            'holidays.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
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
            'hours.*.closes_at.after' => __('The closing time must be later than the opening time.'),
            'holidays.*.date.distinct' => __('This date is already in the list.'),
        ];
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
                $hasWorkingDay = collect($this->input('hours', []))->contains(fn (mixed $day): bool => is_array($day) && filter_var($day['is_working'] ?? false, FILTER_VALIDATE_BOOLEAN));

                if (! $hasWorkingDay) {
                    $validator->errors()->add('hours', __('Choose at least one working day.'));
                }
            },
        ];
    }
}
