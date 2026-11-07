<?php

namespace App\Http\Requests\Desk;

use App\Domain\Ticketing\Enums\TicketPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketPriorityRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
