<?php

namespace App\Http\Requests\Desk;

use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferTicketRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => [
                'required',
                'integer',
                Rule::exists(Team::class, 'id')->where('is_active', true),
                Rule::notIn([$this->ticket()->team_id]),
            ],
            'reason' => ['required', 'string', 'max:500'],
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
            'team_id.exists' => __('Choose an active team.'),
            'team_id.not_in' => __('The ticket is already with that team.'),
        ];
    }

    private function ticket(): Ticket
    {
        return $this->route('ticket');
    }
}
