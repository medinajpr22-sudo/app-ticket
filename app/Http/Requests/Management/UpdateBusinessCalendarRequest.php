<?php

namespace App\Http\Requests\Management;

use App\Domain\ServiceLevel\Models\BusinessCalendar;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBusinessCalendarRequest extends StoreBusinessCalendarRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => ['required', 'string', 'max:100', Rule::unique(BusinessCalendar::class)->ignore($this->calendar())],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * The default calendar stops being the default only when another one takes its place.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if ($this->calendar()->is_default && ! $this->boolean('is_default')) {
                    $validator->errors()->add('is_default', __('There must always be a default calendar. Mark another one as default instead.'));
                }
            },
        ];
    }

    private function calendar(): BusinessCalendar
    {
        return $this->route('businessCalendar');
    }
}
